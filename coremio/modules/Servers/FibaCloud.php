<?php
    class FibaCloud_Module extends ServerModule {
       private $apiUrl = 'https://cloud.fibacloud.com/api';
       
       public $orderId;
       public $vmId;
       
       protected $username;
       protected $password;
       
private function callAPI($method, $url, $data = false) {
    $curl = curl_init();

    $username = $this->server["username"];
    $password = $this->server["password"];
    $authHeader = 'Authorization: Basic ' . base64_encode("$username:$password");

    switch ($method) {
        case "POST":
            curl_setopt($curl, CURLOPT_POST, 1);
            if ($data) curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            break;
        case "PUT":
            curl_setopt($curl, CURLOPT_PUT, 1);
            break;
        default:
            if ($data) $url = sprintf("%s?%s", $url, http_build_query($data));
    }

    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array($authHeader, 'Content-Type: application/json'));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_TIMEOUT, 30);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);

    $result = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    if (curl_errno($curl)) {
        $error_msg = curl_error($curl);
        curl_close($curl);
        
        // Log the error
        self::save_log(
            'Servers',
            $this->_name,
            'callAPI',
            ['url' => $url, 'method' => $method, 'data' => $data],
            'cURL Error: ' . $error_msg,
            ''
        );
        
        return false;
    }

    curl_close($curl);

    // Check HTTP status code
    if ($httpCode >= 400) {
        self::save_log(
            'Servers',
            $this->_name,
            'callAPI',
            ['url' => $url, 'method' => $method, 'data' => $data, 'http_code' => $httpCode],
            'API Error: HTTP ' . $httpCode,
            $result
        );
        
        if ($httpCode == 401) {
            $this->error = 'Authentication failed. Please check username and password.';
        } elseif ($httpCode == 403) {
            $this->error = 'Access denied. Please check API permissions.';
        } elseif ($httpCode == 404) {
            $this->error = 'API endpoint not found.';
        } else {
            $this->error = 'API request failed with HTTP ' . $httpCode;
        }
        
        return false;
    }

    $decoded = json_decode($result, true);
    
    // Check if response is valid JSON
    if (json_last_error() !== JSON_ERROR_NONE) {
        self::save_log(
            'Servers',
            $this->_name,
            'callAPI',
            ['url' => $url, 'method' => $method, 'data' => $data],
            'Invalid JSON response',
            $result
        );
        return false;
    }

    return $decoded;
}
function __construct($server,$options=[]) {
            $this->_name = __CLASS__;
            parent::__construct($server,$options);
        }
protected function define_server_info($server=[]) {
        $this->username = $server["username"];
        $this->password = $server["password"];
      }
public function config_options($data=[]) {
        return [
          'product_id' => [
            'name' => $this->lang["productname"],
            'description' => $this->lang["productnamedesc"],
            'type' => "dropdown",
            'options' => [
                '2063' => "Shared 1",
                '2064' => "Shared 2",
                '2065' => "Shared 3",
                '2066' => "Shared 4",
                '2067' => "Shared 5",
                '2068' => "Shared 6",
                '2069' => "Shared 7",
                '2070' => "Shared 8",
                '2071' => "Dedicated 1",
                '2072' => "Dedicated 2",
                '2073' => "Dedicated 3",
                '2074' => "Dedicated 4",
                '2075' => "Dedicated 5",
                '2076' => "Dedicated 6",
                '2077' => "Dedicated 7",
                '2078' => "Dedicated 8",
                '2079' => "High Memory 1",
                '2080' => "High Memory 2",
                '2081' => "High Memory 3",
                '2082' => "High Memory 4",
            ],
            'value' => isset($data["product_id"]) ? $data["product_id"] : "",
          ],
          'promocode' => [
              'name' => $this->lang["promocodename"],
              'description' => $this->lang["promocodedesc"],
              'type' => "text",
              'width' => "100",
              'value' => isset($data["promocode"]) ? $data["promocode"] : "",
              'placeholder' => $this->lang["promocodename"],
          ],
         ];
}

public function create(array $order_options=[]) {
    $config = $this->order["options"]["creation_info"];
    $product_id = $config["product_id"];
    $promocode = $config['promocode'] ?? '';
    $username = $this->server["username"];
    $password = $this->server["password"];
    
    // Step 1: Get product information and OS mapping
    $osInfoUrl = "https://cloud.fibacloud.com/api/order/{$product_id}";
    $osInfoResponse = $this->callAPI('GET', $osInfoUrl);
    
    if (!$osInfoResponse || !isset($osInfoResponse['product']['config']['forms'])) {
        $this->error = 'Failed to get product information from API.';
        return false;
    }
    
    $templateId = null;
    foreach ($osInfoResponse['product']['config']['forms'] as $form) {
        if ($form['title'] == 'OS') {
            $templateId = $form['id'];
            break;
        }
    }

    if (!$templateId) {
        $this->error = 'Template ID could not be found.';
        return false;
    }

    $selectedOsName = $this->val_of_requirements['Operating System'];

    $osId = null;
    foreach ($osInfoResponse['product']['config']['forms'] as $form) {
        if ($form['title'] == 'OS') {
            foreach ($form['items'] as $item) {
                if ($item['title'] == $selectedOsName) {
                    $osId = $item['id'];
                    break;
                }
            }
        }
    }

    if (!$osId) {
        $this->error = 'OS ID could not be found for the selected OS: ' . $selectedOsName;
        return false;
    }
    
    // Step 2: Send order via POST
    $url = "https://cloud.fibacloud.com/api/order/{$product_id}";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        "cycle" => "m",
        "promocode" => $promocode,
        "custom" => [
            $templateId => $osId
        ]
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Basic ' . base64_encode("$username:$password"),
        'Content-Type: application/json',
    ]);

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        $this->error = curl_error($ch);
        curl_close($ch);
        return false;
    }

    $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpStatusCode != 200) {
        $this->error = "API request failed with status code $httpStatusCode: $response";
        return false;
    }

    $result = json_decode($response, true);
    if(!isset($result['items'][0]['id'])) {
        $this->error = 'Order ID not found in API response: ' . json_encode($result);
        return false;
    }
    
    $this->orderId = $result['items'][0]['id'];
    
    // Step 3: Wait for service to become active and get VM ID
    $vmId = $this->waitForServiceActive($this->orderId);
    if (!$vmId) {
        $this->error = 'Failed to get VM ID from active service.';
        return false;
    }
    
    $this->vmId = $vmId;
    
    // Step 4: Get VM details
    $vmDetailsUrl = "https://cloud.fibacloud.com/api/service/{$this->orderId}/vms/{$vmId}";
    $vmDetailsResponse = $this->callAPI('GET', $vmDetailsUrl);

    if (!isset($vmDetailsResponse['vm'])) {
        $this->error = 'VM details could not be retrieved.';
        return false;
    }
    
    $vm = $vmDetailsResponse['vm'];

    return [
        'ip' => $vm['ipv4'],
        'assigned_ips' => [$vm['ipv4'], $vm['ipv6']],
        'login' => [
            'username' => $vm['username'],
            'password' => $vm['password'],
        ],
        'config' => [
            'vm_id' => $vm['id'],
            'orderId' => $this->orderId,
            'status' => $vm['status'],
            'memory' => $vm['memory'],
            'disk' => $vm['disk'],
            'uptime' => $vm['uptime'],
            'template_name' => $vm['template_name'],
            'cores' => $vm['cores'],
            'sockets' => $vm['sockets'],
            'mac' => $vm['mac'],
            'label' => $vm['label'],
            'cpus' => $vm['cpus'],
        ],
    ];
}

/**
 * Wait for service to become active and return VM ID
 */
private function waitForServiceActive($orderId, $maxWait = 300) { // 5 minutes max wait
    $start = time();
    
    while (time() - $start < $maxWait) {
        $orderDetailsUrl = "https://cloud.fibacloud.com/api/service/{$orderId}/vms/";
        $orderDetailsResponse = $this->callAPI('GET', $orderDetailsUrl);
        
        if (!$orderDetailsResponse) {
            sleep(10);
            continue;
        }
        
        // Check if service is active and has VMs
        if (isset($orderDetailsResponse['vms']) && !empty($orderDetailsResponse['vms'])) {
            $vmId = array_key_first($orderDetailsResponse['vms']);
            if ($vmId) {
                return $vmId;
            }
        }
        
        // Check service status if available
        if (isset($orderDetailsResponse['status'])) {
            if ($orderDetailsResponse['status'] === 'active' || $orderDetailsResponse['status'] === 'running') {
                // Service is active, wait a bit more for VMs to be ready
                sleep(15);
                continue;
            } elseif ($orderDetailsResponse['status'] === 'cancelled' || $orderDetailsResponse['status'] === 'failed') {
                $this->error = 'Service creation failed with status: ' . $orderDetailsResponse['status'];
                return false;
            }
        }
        
        sleep(10);
    }
    
    $this->error = 'Service did not become active within ' . ($maxWait / 60) . ' minutes.';
    return false;
}

public function suspend() {
    try
    {
        $config = $this->order["options"]["config"];

        $order = $config["orderId"];
        $vm = $config["vm_id"];

        $url = "https://cloud.fibacloud.com/api/service/$order/vms/$vm/shutdown";

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Basic ' . base64_encode($this->server["username"] . ":" . $this->server["password"]),
            'Content-Type: application/json',
        ]);

        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new Exception(curl_error($ch));
        }

        $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpStatusCode != 200) {
            throw new Exception("API request failed with status code $httpStatusCode: $response");
        }

        $result = json_decode($response, true);

        if (!isset($result['status']) || $result['status'] !== true) {
            throw new Exception("API request failed: " . ($result['message'] ?? 'Unknown error'));
        }
    }
    catch (Exception $e){
        $this->error = $e->getMessage();
        self::save_log(
            'Servers',
            $this->_name,
            __FUNCTION__,
            ['order' => $this->order],
            $e->getMessage(),
            $e->getTraceAsString()
        );
        return false;
    }

    return true;
}
public function unsuspend() {
    try
    {
        $config = $this->order["options"]["config"];

        $order = $config["orderId"];
        $vm = $config["vm_id"];

        $url = "https://cloud.fibacloud.com/api/service/$order/vms/$vm/start";

        $result = $this->callAPI('POST', $url);

        if (isset($result['status']) && $result['status'] === true) {
            return true;
        } else {
            $this->error = $result['message'] ?? 'Unknown error';
            return false;
        }
    }
    catch (Exception $e){
        $this->error = $e->getMessage();
        self::save_log(
            'Servers',
            $this->_name,
            __FUNCTION__,
            ['order' => $this->order],
            $e->getMessage(),
            $e->getTraceAsString()
        );
        return false;
    }
}
public function terminate() {
    try {
        $config = $this->order["options"]["config"];
        $order = $config["orderId"];
        $url = "https://cloud.fibacloud.com/api/service/$order/cancel";

        $username = $this->server["username"];
        $password = $this->server["password"];
        $authHeader = 'Authorization: Basic ' . base64_encode("$username:$password");

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['immediate' => 'true', 'reason' => 'terminated']));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array($authHeader, 'Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new Exception(curl_error($ch));
        }
        curl_close($ch);

        $result = json_decode($response, true);
        if (isset($result['info']) && (in_array('cancell_sent', $result['info']) || in_array('cancelled_already', $result['info']))) {
            return true;
        } else {
            $this->error = $result['message'] ?? 'An unknown error occurred.';
            return false;
        }
    } catch (Exception $e) {
        $this->error = $e->getMessage();
        self::save_log(
            'Servers',
            $this->_name,
            __FUNCTION__,
            ['order' => $this->order],
            $e->getMessage(),
            $e->getTraceAsString()
        );
        return false;
    }
}
public function get_status() {
    try
    {
        $config = $this->order["options"]["config"];

        $order = $config["orderId"];
        $vm = $config["vm_id"];

        $url = "https://cloud.fibacloud.com/api/service/$order/vms/$vm/";

        $result = $this->callAPI('GET', $url);

        if (isset($result['vm']['status'])) {
            if ($result['vm']['status'] == 'running') {
                return true;
            } elseif ($result['vm']['status'] == 'stopped') {
                return false;
            } else {
                $this->error = 'Unknown VM status: ' . $result['vm']['status'];
                return false;
            }
        } else {
            $this->error = 'VM status not found in API response';
            return false;
        }
    }
    catch (Exception $e){
        $this->error = $e->getMessage();
        self::save_log(
            'Servers',
            $this->_name,
            __FUNCTION__,
            ['order' => $this->order],
            $e->getMessage(),
            $e->getTraceAsString()
        );
        return false;
    }
}
public function clientArea() {
    $templates = $this->getTemplates();

    $content = '';
    $_page = $this->page;
    $_data = [];

    if (!$_page) $_page = 'home';

    if ($_page == "home") {
        $vmDetails = $this->getVmDetails();
        $_data = [
            'id' => $vmDetails['id'],
            'ha' => $vmDetails['ha'],
            'status' => $vmDetails['status'],
            'username' => $vmDetails['username'],
            'password' => $vmDetails['password'],
            'memory' => $vmDetails['memory'],
            'disk' => $vmDetails['disk'],
            'uptime' => $vmDetails['uptime'],
            'template_name' => $vmDetails['template_name'],
            'ipv4' => $vmDetails['ipv4'],
            'ipv6' => $vmDetails['ipv6'],
            'bandwidth' => $vmDetails['bandwidth'],
            'storage' => $vmDetails['storage'],
            'cores' => $vmDetails['cores'],
            'sockets' => $vmDetails['sockets'],
            'mac' => $vmDetails['mac'],
            'label' => $vmDetails['label'],
            'ip' => $vmDetails['ip'],
            'cpus' => $vmDetails['cpus'],
        ];
    }
    $content = '<div class="vm-details">';

    if ($_page == "home") {
        $vmDetails = $this->getVmDetails();
        $content .= '<h2>VM Details</h2>';
          if ($vmDetails['status'] == "running") {
              $progressClass = "progress-100";
              $progressPercent = "100";
          } elseif ($vmDetails['status'] == "starting") {
              $progressClass = "progress-50";
              $progressPercent = "50";
          } elseif ($vmDetails['status'] == "stopping") {
              $progressClass = "progress-50";
              $progressPercent = "50";
          } elseif ($vmDetails['status'] == "shutdown") {
              $progressClass = "progress-50";
              $progressPercent = "50";
          } else {
              $progressClass = "progress-0";
              $progressPercent = "0";
          }

        $content .= '<div style="margin-bottom:20px;display:inline-block;text-align:center;"><h5 style="font-size:16px;"><strong>VM Status</strong></h5><div class="clear"></div><div class="progress-circle ' . $progressClass . '"><span>' . $progressPercent . '</span></div><div class="clear"></div><h5 style="font-size:16px;">' . $vmDetails['status'] . '</h5></div>';
        $content .= '<div style="margin-bottom:20px;display:inline-block;text-align:center;"><h5 style="font-size:16px;"><strong>Storage</strong></h5><div class="clear"></div><div class="progress-circle progress-100"><span>100</span></div><div class="clear"></div><h5 style="font-size:16px;">' . $vmDetails['disk'] . ' GB</h5></div>';
        $content .= '<div style="margin-bottom:20px;display:inline-block;text-align:center;"><h5 style="font-size:16px;"><strong>RAM</strong></h5><div class="clear"></div><div class="progress-circle progress-100"><span>100</span></div><div class="clear"></div><h5 style="font-size:16px;">' . $vmDetails['memory'] . ' MB</h5></div>';
        $content .= '<div style="margin-bottom:20px;display:inline-block;text-align:center;"><h5 style="font-size:16px;"><strong>CPU</strong></h5><div class="clear"></div><div class="progress-circle progress-100"><span>100</span></div><div class="clear"></div><h5 style="font-size:16px;">' . $vmDetails['cpus'] . ' vCPU</h5></div>';
    }
    
    if ($_page == "rebuild") {
    $content .= '<h2>Rebuild VM</h2>';
      foreach ($templates as $template) {
       $content .= '<a href="javascript:void(0);" class="hostbtn os" onclick="if(confirm(\'' . addslashes($this->lang["rebuildconfirm"]) . '\')) { run_transaction(\'rebuildVM\',this); }" data-fields=\'{"' . htmlspecialchars($template['id'], ENT_QUOTES, 'UTF-8') . '":"' . htmlspecialchars($template['name'], ENT_QUOTES, 'UTF-8') . '"}\'>' . htmlspecialchars($template['name'], ENT_QUOTES, 'UTF-8') . '</a>';
      }
    }
    $content .= '</div>';
    $content .= $this->clientArea_buttons_output();
    $content .= $this->get_page('clientArea-' . $_page, $_data);
    return $content;
}
public function getVmDetails() {
    $config = $this->order["options"]["config"];

    $order = $config["orderId"];
    $vm = $config["vm_id"];

    $url = "https://cloud.fibacloud.com/api/service/$order/vms/$vm/";

    $result = $this->callAPI('GET', $url);

    return $result['vm'];
}
public function clientArea_buttons() {
    $buttons = [];

    if ($this->page && $this->page != "home") {
        $buttons['home'] = [
            'text' => $this->lang["turn-back"],
            'type' => 'page-loader',
        ];} 
    else {
        $status = $this->getVmStatus();
        if ($status == 'running') {
            $buttons['restart'] = [
                'text' => $this->lang["restart"],
                'icon' => 'fa fa-exchange',
                'type' => 'transaction',
            ];
            $buttons['reboot'] = [
                'text' => $this->lang["reboot"],
                'icon' => 'fa fa-retweet',
                'type' => 'transaction',
            ];
            $buttons['stop'] = [
                'text' => $this->lang["stop"],
                'icon' => 'fa fa-power-off',
                'type' => 'transaction',
            ];
            $buttons['hardstop'] = [
                'text' => $this->lang["hardstop"],
                'icon' => 'fa fa-window-close',
                'type' => 'transaction',
            ];}
        elseif ($status == 'stopped') {$buttons['start'] = [
                'text' => $this->lang["start"],
                'icon' => 'fa fa-toggle-on',
                'type' => 'transaction',
            ];}
        elseif ($status == 'starting') {$buttons['starting'] = [
                'text' => $this->lang["starting"],
                'icon' => 'fa fa-clock-o',
            ];}
        elseif ($status == 'stopping') {$buttons['stopping'] = [
                'text' => $this->lang["stopping"],
                'icon' => 'fa fa-clock-o',
            ];}
        elseif ($status == 'shutdown') {$buttons['stopping'] = [
                'text' => $this->lang["stopping"],
                'icon' => 'fa fa-clock-o',
            ];}
        $buttons['rebuild'] = [
            'text'  => $this->lang["rebuild"],
            'type'  => 'page-loader',
        ];
    }
    return $buttons;
}
public function use_clientArea_rebuildVM(){
    $username = $this->server["username"];
    $password = $this->server["password"];
    $templates = $this->getTemplates(); 
    $config = $this->order["options"]["config"];
    $order = $config["orderId"];
    $vm = $config["vm_id"];

    foreach ($templates as $template) {
        if (Filter::POST($template['id'])) {
            $apiUrl = "https://cloud.fibacloud.com/api/service/$order/vms/$vm/rebuild";
            $ch = curl_init($apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Basic ' . base64_encode("$username:$password"),
                'Content-Type: application/json',
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'template' => $template['id']
            ]));
            $response = curl_exec($ch);
            $err = curl_error($ch);
            curl_close($ch);
            if ($err) {
                echo "cURL Error #:" . $err;
            } else {
                $responseArray = json_decode($response, true);
                if (isset($responseArray['status']) && $responseArray['status'] == 1 && !isset($responseArray['error'])) {
                    $u_data = UserManager::LoginData('member');
                    $user_id = $u_data['id'];

                    User::addAction($user_id, 'transaction', " " . htmlspecialchars($template['name'], ENT_QUOTES, 'UTF-8') . ' Rebuild Command sent for service #' . $this->order["id"]);
                    Orders::add_history($user_id, $this->order["id"], 'Server Order Rebuild');

                    echo Utility::jencode([
                        'status' => "successful",
                        'message' => $this->lang["rebuildsuccessful"] . " " . htmlspecialchars($template['name'], ENT_QUOTES, 'UTF-8'),
                        'timeRedirect' => [
                        'url' => $this->area_link,
                        'duration' => 5000
                ],
                    ]);
                } else {
                    $errorMessage = isset($responseArray['error']) ? implode(", ", $responseArray['error']) : "Unknown error";
                    echo Utility::jencode([
                        'status' => "error",
                        'message' => $this->lang["rebuilderoor"] . " " . htmlspecialchars($template['name'], ENT_QUOTES, 'UTF-8') . " - Error: " . $errorMessage,
                    ]);
                }
            }
            break;
        }
    }
    return true;
}
function getTemplates() { 
    $config = $this->order["options"]["config"];
    $orderid = $config["orderId"];
    
    $curl = curl_init();

    curl_setopt_array($curl, [
        CURLOPT_URL => "https://cloud.fibacloud.com/api/service/$orderid/templates",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPGET => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Basic ' . base64_encode($this->server["username"] . ":" . $this->server["password"]),
            'Content-Type: application/json',
        ],
    ]);

    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);

    if ($err) {
        return "cURL Error #:" . $err;
    } else {
        return json_decode($response, true)['templates'];
    }
}
public function getVmStatus() {
    $config = $this->order["options"]["config"];

    $order = $config["orderId"];
    $vm = $config["vm_id"];

    $url = "https://cloud.fibacloud.com/api/service/$order/vms/$vm/";

    $result = $this->callAPI('GET', $url);

    return $result['vm']['status'];
}
public function use_clientArea_start() {
    if ($this->start()) {
        $u_data = UserManager::LoginData('member');
        $user_id = $u_data['id'];

        User::addAction($user_id, 'transaction', '"Start" Command sent for service #' . $this->order["id"]);
        Orders::add_history($user_id, $this->order["id"], 'server-order-start');

        return true;
    }
    return false;
}
public function use_clientArea_stop() {
    if ($this->stop()) {
        $u_data = UserManager::LoginData('member');
        $user_id = $u_data['id'];

        User::addAction($user_id, 'transaction', '"Stop" Command sent for service #' . $this->order["id"]);
        Orders::add_history($user_id, $this->order["id"], 'server-order-stop');

        return true;
    }
    return false;
}
public function use_clientArea_hardstop() {
    if ($this->hardstop()) {
        $u_data = UserManager::LoginData('member');
        $user_id = $u_data['id'];

        User::addAction($user_id, 'transaction', '"Hard Stop" Command sent for service #' . $this->order["id"]);
        Orders::add_history($user_id, $this->order["id"], 'server-order-stop');

        return true;
    }
    return false;
}
public function use_clientArea_restart() {
    if ($this->restart()) {
        $u_data = UserManager::LoginData('member');
        $user_id = $u_data['id'];

        User::addAction($user_id, 'transaction', '"Restart" Command sent for service #' . $this->order["id"]);
        Orders::add_history($user_id, $this->order["id"], 'server-order-restart');

        return true;
    }
    return false;
}
public function use_clientArea_reboot() {
    if ($this->reboot()) {
        $u_data = UserManager::LoginData('member');
        $user_id = $u_data['id'];

        User::addAction($user_id, 'transaction', '"Reboot" Command sent for service #' . $this->order["id"]);
        Orders::add_history($user_id, $this->order["id"], 'server-order-reboot');

        return true;
    }
    return false;
}

public function adminArea_service_fields() {
            $c_info                 = $this->options["creation_info"];
        }
public function adminArea_buttons() {
            $buttons = [];

            $status = $this->getVmStatus();

            if($status == 'running')
            {
                $buttons['restart']     = [
                    'text'  => $this->lang["restart"],
                    'type'  => 'transaction',
                ];

                $buttons['reboot']      = [
                    'text'  => $this->lang["reboot"],
                    'type'  => 'transaction',
                ];
                $buttons['stop']      = [
                    'text'  => $this->lang["stop"],
                    'type'  => 'transaction',
                ];
            }
            elseif($status == 'stopped')
            {
                $buttons['start']      = [
                    'text'  => $this->lang["start"],
                    'type'  => 'transaction',
                ];
            }

            $buttons['another-link'] = [
                'text'      => 'FibaCloud Cloud Control',
                'type'      => 'link',
                'url'       => 'https://cloud.fibacloud.com',
                'target_blank' => true,
            ];

            return $buttons;
        }
public function use_adminArea_start() {
            $this->area_link .= '?content=automation';
            if($this->start()){
                $u_data     = UserManager::LoginData('admin');
                $user_id    = $u_data['id'];
                User::addAction($user_id,'transaction','"Start" Command sent for service #'.$this->order["id"]);
                Orders::add_history($user_id,$this->order["id"],'server-order-start');
                return true;
            }
            return false;
        }
public function use_adminArea_stop() {
            $this->area_link .= '?content=automation';
            if($this->stop()){
                $u_data     = UserManager::LoginData('admin');
                $user_id    = $u_data['id'];
                User::addAction($user_id,'transaction','"Stop" Command sent for service #'.$this->order["id"]);
                Orders::add_history($user_id,$this->order["id"],'server-order-stop');
                return true;
            }
            return false;
        }
public function use_adminArea_restart() {
            $this->area_link .= '?content=automation';
            if($this->restart()){
                $u_data     = UserManager::LoginData('admin');
                $user_id    = $u_data['id'];
                User::addAction($user_id,'transaction','"Restart" Command sent for service #'.$this->order["id"]);
                Orders::add_history($user_id,$this->order["id"],'server-order-restart');
                return true;
            }
            return false;
        }
public function use_adminArea_reboot() {
            $this->area_link .= '?content=automation';
            if($this->reboot()){
                $u_data     = UserManager::LoginData('admin');
                $user_id    = $u_data['id'];
                User::addAction($user_id,'transaction','"Reboot" Command sent for service #'.$this->order["id"]);
                Orders::add_history($user_id,$this->order["id"],'server-order-reboot');
                return true;
            }
            return false;
        }
        
public function start() {
    try {
        $config = $this->order["options"]["config"];
        $order = $config["orderId"];
        $vm = $config["vm_id"];
        $url = "https://cloud.fibacloud.com/api/service/$order/vms/$vm/start";
        $result = $this->callAPI('POST', $url);

        if (isset($result['status']) && $result['status'] === true) {
            echo Utility::jencode([
                'status' => "successful",
                'message' => $this->lang["successful"],
                'timeRedirect' => [
                    'url' => $this->area_link,
                    'duration' => 5000
                ],
            ]);
        } else {
            $this->error = 'Failed to start VM';
            echo Utility::jencode([
                'status' => "error",
                'message' => $this->error,
            ]);
        }
    } catch (Exception $e) {
        $this->error = $e->getMessage();
        self::save_log(
            'Servers',
            $this->_name,
            __FUNCTION__,
            ['order' => $this->order],
            $e->getMessage(),
            $e->getTraceAsString()
        );
        echo Utility::jencode([
            'status' => "error",
            'message' => $this->error,
        ]);
    }
    return isset($result['status']) && $result['status'] === true;
}
public function stop() {
    try {
        $config = $this->order["options"]["config"];
        $order = $config["orderId"];
        $vm = $config["vm_id"];
        $url = "https://cloud.fibacloud.com/api/service/$order/vms/$vm/shutdown";
        $result = $this->callAPI('POST', $url);

        if (isset($result['status']) && $result['status'] === true) {
            echo Utility::jencode([
                'status' => "successful",
                'message' => $this->lang["successful"],
                'timeRedirect' => [
                    'url' => $this->area_link,
                    'duration' => 5000
                ],
            ]);
        } else {
            $this->error = 'Failed to stop VM';
            echo Utility::jencode([
                'status' => "error",
                'message' => $this->error,
            ]);
        }
    } catch (Exception $e) {
        $this->error = $e->getMessage();
        self::save_log(
            'Servers',
            $this->_name,
            __FUNCTION__,
            ['order' => $this->order],
            $e->getMessage(),
            $e->getTraceAsString()
        );
        echo Utility::jencode([
            'status' => "error",
            'message' => $this->error,
        ]);
    }
    return isset($result['status']) && $result['status'] === true;
}
public function hardstop() {
    try {
        $config = $this->order["options"]["config"];
        $order = $config["orderId"];
        $vm = $config["vm_id"];
        $url = "https://cloud.fibacloud.com/api/service/$order/vms/$vm/stop";
        $result = $this->callAPI('POST', $url);

        if (isset($result['status']) && $result['status'] === true) {
            echo Utility::jencode([
                'status' => "successful",
                'message' => $this->lang["successful"],
                'timeRedirect' => [
                    'url' => $this->area_link,
                    'duration' => 5000
                ],
            ]);
        } else {
            $this->error = 'Failed to stop VM';
            echo Utility::jencode([
                'status' => "error",
                'message' => $this->error,
            ]);
        }
    } catch (Exception $e) {
        $this->error = $e->getMessage();
        self::save_log(
            'Servers',
            $this->_name,
            __FUNCTION__,
            ['order' => $this->order],
            $e->getMessage(),
            $e->getTraceAsString()
        );
        echo Utility::jencode([
            'status' => "error",
            'message' => $this->error,
        ]);
    }
    return isset($result['status']) && $result['status'] === true;
}
public function restart() {
    try {
        $config = $this->order["options"]["config"];
        $order = $config["orderId"];
        $vm = $config["vm_id"];
        $url = "https://cloud.fibacloud.com/api/service/$order/vms/$vm/reset";
        $result = $this->callAPI('POST', $url);

        if (isset($result['status']) && $result['status'] === true) {
            echo Utility::jencode([
                'status' => "successful",
                'message' => $this->lang["successful"],
                'timeRedirect' => [
                    'url' => $this->area_link,
                    'duration' => 5000
                ],
            ]);
        } else {
            $this->error = 'Failed to restart VM';
            echo Utility::jencode([
                'status' => "error",
                'message' => $this->error,
            ]);
        }
    } catch (Exception $e) {
        $this->error = $e->getMessage();
        self::save_log(
            'Servers',
            $this->_name,
            __FUNCTION__,
            ['order' => $this->order],
            $e->getMessage(),
            $e->getTraceAsString()
        );
        echo Utility::jencode([
            'status' => "error",
            'message' => $this->error,
        ]);
    }
    return isset($result['status']) && $result['status'] === true;
}
public function reboot() {
    try {
        $config = $this->order["options"]["config"];
        $order = $config["orderId"];
        $vm = $config["vm_id"];
        $url = "https://cloud.fibacloud.com/api/service/$order/vms/$vm/reboot";
        $result = $this->callAPI('POST', $url);

        if (isset($result['status']) && $result['status'] === true) {
            echo Utility::jencode([
                'status' => "successful",
                'message' => $this->lang["successful"],
                'timeRedirect' => [
                    'url' => $this->area_link,
                    'duration' => 5000
                ],
            ]);
        } else {
            $this->error = 'Failed to reboot VM';
            echo Utility::jencode([
                'status' => "error",
                'message' => $this->error,
            ]);
        }
    } catch (Exception $e) {
        $this->error = $e->getMessage();
        self::save_log(
            'Servers',
            $this->_name,
            __FUNCTION__,
            ['order' => $this->order],
            $e->getMessage(),
            $e->getTraceAsString()
        );
        echo Utility::jencode([
            'status' => "error",
            'message' => $this->error,
        ]);
    }
    return isset($result['status']) && $result['status'] === true;
}

/**
 * Test API connectivity and authentication
 */
public function testConnection() {
    try {
        $username = $this->server["username"];
        $password = $this->server["password"];
        
        if (empty($username) || empty($password)) {
            return [
                'status' => 'error',
                'message' => 'Username or password is empty'
            ];
        }
        
        // Test API connection by getting available products
        $testUrl = "https://cloud.fibacloud.com/api/order";
        $response = $this->callAPI('GET', $testUrl);
        
        if (!$response) {
            return [
                'status' => 'error',
                'message' => 'Failed to connect to API or authentication failed'
            ];
        }
        
        if (isset($response['error']) || isset($response['message'])) {
            return [
                'status' => 'error',
                'message' => 'API Error: ' . ($response['error'] ?? $response['message'])
            ];
        }
        
        return [
            'status' => 'success',
            'message' => 'API connection successful'
        ];
        
    } catch (Exception $e) {
        return [
            'status' => 'error',
            'message' => 'Exception: ' . $e->getMessage()
        ];
    }
}
}

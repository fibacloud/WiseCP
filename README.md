# FibaCloud.com WiseCP Reseller Modules
FibaCloud.com WiseCP Reseller Modules

## API Workflow

This module follows a specific API workflow for VPS provisioning:

### 1. Authentication
- Every API call uses Basic Authentication with username and password
- Credentials are validated before any operation

### 2. Product and OS Mapping
- Fetches available products from `/api/order/{product_id}`
- Maps selected OS from requirements to API OS ID
- Validates OS compatibility with selected product

### 3. Order Creation
- Sends POST request to `/api/order/{product_id}`
- Includes OS template ID and OS ID in custom fields
- Returns order ID for tracking

### 4. Service Activation
- Polls `/api/service/{order_id}/vms/` until service becomes active
- Waits for VMs to be provisioned
- Extracts VM ID from the first available VM

### 5. VM Operations
- All subsequent operations use the obtained VM ID
- Operations: start, stop, restart, reboot, suspend, terminate
- Each operation validates VM status before proceeding

## Installation
- Download latest module
- Upload archive folder contents to your WiseCP installation root directory;
- Login to WseCP admin panel;
- Go to Products / Services > Hosting/Server > Shared Server Settings > Add New Shared Server
- Hostname: **Label**
- Server Automation Type: **FibaCloud**
- IP Adresi: **cloud.fibacloud.com**
- Username: **Cloud Account Email**
- Password: **Cloud Account Password**
- Upgrade/Downgrade Settings: **Not Delete**
- Click on the **Add New Shared Server button**

## OS Requirement Configuration
- Go to Products/Services > Product/Service Management > Product/Service Requirements > Create New Requirement
- Requirement Name: **OS**
- Requirement Group: **Servers**
- Related Product Group: **Server**
- Difficulty: **enabled**
- Configurable Options: **FibaCloud Operating System**
- Option Type: **Dropdown Menu (Selectbox)**
- Options:
   - CentOS 7 - CentOS 7
   - CentOS 8 Stream - CentOS 8 Stream
   - CentOS 9 Stream - CentOS 9 Stream
   - AlmaLinux 8 - AlmaLinux 8
   - AlmaLinux 9 - AlmaLinux 9
   - Debian 10 - Debian 10
   - Debian 11 - Debian 11
   - Debian 12 - Debian 12
   - Debian 13 - Debian 13
   - Ubuntu 18.04 - Ubuntu 18.04
   - Ubuntu 20.04 - Ubuntu 20.04
   - Ubuntu 22.04 - Ubuntu 22.04
   - Ubuntu 23.04 - Ubuntu 23.04
   - Ubuntu 23.10 - Ubuntu 23.10
   - Rocky Linux 8 - Rocky Linux 8
   - Rocky Linux 9 - Rocky Linux 9

 ## Packade Configuration
 - Go to Products/Services > Hosting/Server > Server Packages
 - Edit or Create a Package
    - Go To **Automation Settings**
       - Master Server: **FibaCloud**
       - Select Product: FibaCloud Server Package That Will Match the Package You Edited
       - Promo Code: If you have a promotional code, you can pass the code via API.
       - Automatic Installation: **Enabled**
       - Save Settings
    - Go To **Requirements**
       - Enable **OS**
       - Save Settings

## Troubleshooting

### Common Issues:
1. **Authentication Failed**: Check username and password in server settings
2. **OS Not Found**: Verify OS requirement is properly configured
3. **Service Not Active**: Check if product ID matches FibaCloud package
4. **VM ID Missing**: Ensure service has completed provisioning

### API Testing:
- Use the "Test Connection" feature in server settings
- Check server logs for detailed error messages
- Verify API endpoints are accessible from your server

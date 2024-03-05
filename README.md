# FibaCloud.com WiseCP Reseller Modules
FibaCloud.com WiseCP Reseller Modules

## Installation
- Download latest module [release](https://github.com/fibacloud/WiseCP/blob/main/FibaCloud_WiseCP_V1.zip);
- Upload archive folder contents to your WiseCP installation root directory;
- Login to WseCP admin panel;
- Go to Products / Services > Hosting/Server > Shared Server Settings > Add New Shared Server
- Hostname: **Label**
- Server Automation Type: **FibaCloud**
- IP Adresi: **cloud.fibacloud.com**
- Username: **Cloud Account Email**
- Password: **Cloud Account Password**
- Upgrade/Downgrade Settings: **Silinmesin**
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

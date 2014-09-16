# Shell dataflow import

Magento shell script which allows to start advanced dataflow import profiles.

Fork of H&O snippet: https://gist.github.com/ho-nl/571614


## Installation

You can install the module via modman:

```bash
modman clone git@github.com:wansoft/shell_dataflow_import.git

```
Or:

```bash
modman clone https://github.com/wansoft/shell_dataflow_import.git
```

Rename "dataflow_config.sample.php" :

```PHP
$username = '...';
$password = '...';
$url = '...';
```

Disable SID on frontend:


1. Go to System > Configuration > Web > Session Validation Settings
2. Set Use SID on Frontend to “No”


## Usage


```BASH
php -f dataflow_import.php -- <profile ID>
```

<?php
// ClubCMS — Configuration (généré par install.php)
// Ne pas modifier manuellement les constantes DB

if (!defined('CC_ROOT'))    define('CC_ROOT', dirname(__DIR__));
if (!defined('CC_VERSION')) define('CC_VERSION', '1.4.0');

if(!defined('CC_URL')) define('CC_URL', (isset($_SERVER['HTTPS'])&&$_SERVER['HTTPS']==='on'?'https':'http').'://'.($_SERVER['HTTP_HOST']??'localhost'));
require_once CC_ROOT.'/core/Database.php';
require_once CC_ROOT.'/core/Auth.php';
require_once CC_ROOT.'/core/Config.php';
require_once CC_ROOT.'/core/Mailer.php';
require_once CC_ROOT.'/core/Helpers.php';
require_once CC_ROOT.'/core/BlockRenderer.php';
require_once CC_ROOT.'/core/CriteriaRenderer.php';
require_once CC_ROOT.'/core/ActivityLog.php';
require_once CC_ROOT.'/core/Invoice.php';

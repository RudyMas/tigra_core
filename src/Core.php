<?php

namespace Tiger;

use Exception;
use RudyMas\Manipulator\Text;
use RudyMas\DBconnect;

/**
 * Class Core (PHP version 7.4)
 *
 * @author Rudy Mas <rudy.mas@rmsoft.be>
 * @copyright 2022, rmsoft.be. (http://www.rmsoft.be/)
 * @license https://opensource.org/licenses/GPL-3.0 GNU General Public License, version 3 (GPL-3.0)
 * @version 7.4.1.0
 * @package Tiger
 */
class Core
{
    public array $DB;
    public Login $Login;
    public HttpRequest $HttpRequest;
    public Email $Email;
    public Menu $Menu;

    /**
     * Core constructor.
     */
    public function __construct()
    {
        define('CORE_VERSION', '7.4.1.0');

        $this->settingUpRootMapping();

        require_once('config/version.php');
        require_once('config/server.php');
        require_once('config/config.website.php');
        date_default_timezone_set(TIME_ZONE);

        $this->loadingConfig();
        if (USE_DATABASE) {
            $this->loadingDatabases();
        }
        if (USE_LOGIN && isset($this->DB['DBconnect'])) {
            $this->loadingTigerLogin($this->DB['DBconnect']);
        }
        if (USE_HTTP_REQUEST) {
            $this->loadingTigerHttpRequest();
        }
        if (USE_EMAIL) {
            $this->loadingTigerEmail();
        }
        if (USE_MENU) {
            $this->loadingTigerMenu();
        }

        $Router = new Router($this);
        require_once('config/router.php');
        try {
            $Router->execute();
        } catch (Exception $exception) {
            http_response_code(500);
            print('Tiger : Something went wrong.');
            if (TIGER_DEBUG === true) {
                print('<br><br>');
                print($exception->getMessage());
                print('<br><br>');
                print('<hr>');
                print('<br><br>');
                print('<pre>');
                print_r($exception);
                print('</pre>');
            }
        }
    }

    /**
     * Creating BASE_URL & SYSTEM_ROOT
     *
     * BASE_URL = Path to the root of the website
     * SYSTEM_ROOT = Full system path to the root of the website
     */
    private function settingUpRootMapping(): void
    {
        $arrayServerName = explode('.', $_SERVER['SERVER_NAME']);
        $numberOfServerNames = count($arrayServerName);
        unset($arrayServerName[$numberOfServerNames - 2]);
        unset($arrayServerName[$numberOfServerNames - 1]);

        $scriptName = rtrim(str_replace($arrayServerName, '', dirname($_SERVER['SCRIPT_NAME'])), '/\\');
        define('BASE_URL', $scriptName);

        define('SYSTEM_ROOT', getcwd());
    }

    /**
     * Loading the configuration files for the website
     *
     * Checks if certain files exist, if not, it uses the standard config file by copying it
     */
    private function loadingConfig(): void
    {
        if ($_SERVER['HTTP_HOST'] == SERVER_DEVELOP) {
            if (!is_file(SYSTEM_ROOT . '/config/config.local.php')) {
                @copy(SYSTEM_ROOT . '/config/config.sample.php', SYSTEM_ROOT . '/config/config.local.php');
            }
            require_once('config/config.local.php');
        } elseif ($_SERVER['HTTP_HOST'] == SERVER_ALPHA) {
            if (!is_file(SYSTEM_ROOT . '/config/config.alpha.php')) {
                @copy(SYSTEM_ROOT . '/config/config.sample.php', SYSTEM_ROOT . '/config/config.alpha.php');
            }
            require_once('config/config.alpha.php');
        } elseif ($_SERVER['HTTP_HOST'] == SERVER_BETA) {
            if (!is_file(SYSTEM_ROOT . '/config/config.beta.php')) {
                @copy(SYSTEM_ROOT . '/config/config.sample.php', SYSTEM_ROOT . '/config/config.beta.php');
            }
            require_once('config/config.beta.php');
        } else {
            if (!is_file(SYSTEM_ROOT . '/config/config.php')) {
                @copy(SYSTEM_ROOT . '/config/config.sample.php', SYSTEM_ROOT . '/config/config.php');
            }
            require_once('config/config.php');
        }
    }

    /**
     * Loading the databases for the websites
     * @throws Exception
     */
    private function loadingDatabases(): void
    {
        $database = [];
        if ($_SERVER['HTTP_HOST'] == SERVER_DEVELOP) {
            if (!is_file(SYSTEM_ROOT . '/config/database.local.php')) {
                @copy(SYSTEM_ROOT . '/config/database.sample.php', SYSTEM_ROOT . '/config/database.local.php');
            }
            require_once('config/database.local.php');
        } elseif ($_SERVER['HTTP_HOST'] == SERVER_ALPHA) {
            if (!is_file(SYSTEM_ROOT . '/config/database.alpha.php')) {
                @copy(SYSTEM_ROOT . '/config/database.sample.php', SYSTEM_ROOT . '/config/database.alpha.php');
            }
            require_once('config/database.alpha.php');
        } elseif ($_SERVER['HTTP_HOST'] == SERVER_BETA) {
            if (!is_file(SYSTEM_ROOT . '/config/database.beta.php')) {
                @copy(SYSTEM_ROOT . '/config/database.sample.php', SYSTEM_ROOT . '/config/database.beta.php');
            }
            require_once('config/database.beta.php');
        } else {
            if (!is_file(SYSTEM_ROOT . '/config/database.php')) {
                @copy(SYSTEM_ROOT . '/config/database.sample.php', SYSTEM_ROOT . '/config/database.php');
            }
            require_once('config/database.php');
        }
        foreach ($database as $connect) {
            $object = $connect['objectName'];
            $this->DB[$object] = new DBconnect(
                $connect['dbHost'],
                $connect['port'],
                $connect['dbUsername'],
                $connect['dbPassword'],
                $connect['dbName'],
                $connect['dbCharset'],
                $connect['dbType']
            );
        }
    }

    /**
     * Loading the Tiger Login class
     *
     * @param DBconnect $DBconnect
     */
    private function loadingTigerLogin(DBconnect $DBconnect): void
    {
        $this->Login = new Login($DBconnect, new Text(), USE_EMAIL_LOGIN);
    }

    /**
     * Loading the Tiger HttpRequest class
     */
    private function loadingTigerHttpRequest(): void
    {
        $this->HttpRequest = new HttpRequest();
    }

    /**
     * Loading the Tiger Email class
     */
    private function loadingTigerEmail(): void
    {
        $this->Email = new Email();
        $this->Email->config();
    }

    /**
     * Loading the Tiger Menu class
     */
    private function loadingTigerMenu(): void
    {
        $this->Menu = new Menu();
    }
}

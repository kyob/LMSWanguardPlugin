<?php

class WanguardHandler
{
    public function smartyWanguard(Smarty $hook_data)
    {
        $template_dirs = $hook_data->getTemplateDir();
        $plugin_templates = PLUGINS_DIR . DIRECTORY_SEPARATOR . LMSWanguardPlugin::PLUGIN_DIRECTORY_NAME . DIRECTORY_SEPARATOR . 'templates';
        array_unshift($template_dirs, $plugin_templates);
        $hook_data->setTemplateDir($template_dirs);
        return $hook_data;
    }

    public function modulesDirWanguard(array $hook_data = array())
    {
        $plugin_modules = PLUGINS_DIR . DIRECTORY_SEPARATOR . LMSWanguardPlugin::PLUGIN_DIRECTORY_NAME . DIRECTORY_SEPARATOR . 'modules';
        array_unshift($hook_data, $plugin_modules);
        return $hook_data;
    }

    public function welcomeWanguard(array $hook_data = array())
    {

        $SMARTY = LMSSmarty::getInstance();
        $DB = LMSDB::getInstance();

        if (filter_var(ConfigHelper::getConfig('wanguard.limit'), FILTER_VALIDATE_INT) !== false) {
            $limit = ConfigHelper::getConfig('wanguard.limit');
        } else {
            $limit = 5;
        }

        $anomalies = array();
        $anomalies = $DB->GetAll('SELECT * FROM alfa_wanguard ORDER BY anomaly_id DESC LIMIT ' . $limit);

        $SMARTY->assign(
            array(
                'wanguards' => $anomalies,
                'wanguards_count' => count($anomalies),
            )
        );
        return $hook_data;
    }

    public function nodeInfoBeforeDisplay(array $hook_data = array())
    {

        $DB = LMSDB::getInstance();

        $nid = $_GET['id'];

        if (filter_var(ConfigHelper::getConfig('wanguard.limit'), FILTER_VALIDATE_INT) !== false) {
            $limit = ConfigHelper::getConfig('wanguard.limit');
        } else {
            $limit = 5;
        }

        $anomaly = $DB->GetAll('SELECT * FROM alfa_wanguard WHERE node_id=' . $nid . ' ORDER BY anomaly_id DESC LIMIT ' . $limit);

        $hook_data['nodeinfo']['wanguard'] = $anomaly;

        return $hook_data;
    }


    public function accessTableInit()
    {
        $access = AccessRights::getInstance();
        $access->insertPermission(new Permission(
            'Wanguard_full_access',
            trans('Wanguard'),
            '^Wanguard$'
        ), AccessRights::FIRST_FORBIDDEN_PERMISSION);
    }
}

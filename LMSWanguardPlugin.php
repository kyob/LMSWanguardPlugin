<?php

/**
 * LMSWanguardPlugin
 * 
 * @author Łukasz Kopiszka <lukasz@alfa-system.pl>
 */
class LMSWanguardPlugin extends LMSPlugin
{
    const PLUGIN_NAME = 'LMS Wanguard API plugin';
    const PLUGIN_DESCRIPTION = 'Integration with Wanguard API.';
    const PLUGIN_AUTHOR = 'Łukasz Kopiszka &lt;lukasz@alfa-system.pl&gt;';
    const PLUGIN_DIRECTORY_NAME = 'LMSWanguardPlugin';

    public function registerHandlers()
    {
        $this->handlers = array(
            'smarty_initialized' => array(
                'class' => 'WanguardHandler',
                'method' => 'smartyWanguard'
            ),
            'modules_dir_initialized' => array(
                'class' => 'WanguardHandler',
                'method' => 'modulesDirWanguard'
            ),
            'welcome_before_module_display' => array(
                'class' => 'WanguardHandler',
                'method' => 'welcomeWanguard'
            ),
            'access_table_initialized' => array(
                'class' => 'WanguardHandler',
                'method' => 'accessTableInit'
            ),
            'nodeinfo_before_display' => array(
                'class' => 'WanguardHandler',
                'method' => 'nodeInfoBeforeDisplay'
            )
        );
    }
}

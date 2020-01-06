<?php

/* Copyright (c) 1998-2020 Leifos GmbH, Extended GPL, see docs/LICENSE */

/**
 * LF Main menu plugin
 *
 * @author Alexander Killing <killing@leifos.de>
 */
class ilLfDevToolPlugin extends ilUserInterfaceHookPlugin
{
    /**
     *
     * @param
     * @return
     */
    protected function __construct()
    {
        parent::__construct();

        global $DIC;
        $this->includeClass("class.MetaBarProvider.php");
        $this->provider_collection->setMetaBarProvider(new \Leifos\DevTool\MetaBarProvider($DIC, $this));
    }

    /**
     * @return string
     */
    function getPluginName()
    {
        return "LfDevTool";
    }

}

<?php

/* Copyright (c) 1998-2020 Leifos GmbH, Extended GPL, see docs/LICENSE */

/**
 * LF Main menu plugin
 *
 * @author Alexander Killing <killing@leifos.de>
 */
class ilLfDevToolPlugin extends ilUserInterfaceHookPlugin
{
    public function __construct($db = null, $crepo = null, $id = null)
    {
        global $DIC;

        parent::__construct($db, $crepo, $id);
        $this->provider_collection->setMetaBarProvider(new \Leifos\DevTool\MetaBarProvider($DIC, $this));
    }

    /**
     * @return string
     */
    function getPluginName() : string
    {
        return "LfDevTool";
    }

}

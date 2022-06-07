<?php

namespace Leifos\DevTool;

use ILIAS\GlobalScreen\Identification\IdentificationInterface;
use ILIAS\GlobalScreen\Scope\MetaBar\Provider\AbstractStaticMetaBarPluginProvider;
use ILIAS\GlobalScreen\Scope\MetaBar\Provider\StaticMetaBarProvider;
use ILIAS\UI\Component\JavaScriptBindable;

/**
 * Help meta bar provider
 *
 * @author <killing@leifos.de>
 */
class MetaBarProvider extends AbstractStaticMetaBarPluginProvider implements StaticMetaBarProvider
{
    /**
     * @return IdentificationInterface
     */
    private function getId() : IdentificationInterface
    {
        return $this->if->identifier('lfdevtool');
    }


    /**
     * @inheritDoc
     */
    public function getMetaBarItems() : array
    {
        global $DIC;

        $mb = $this->globalScreen()->metaBar();

        $f = $DIC->ui()->factory();

        $title = "DevTool";

        $icon_path = \ilUtil::getImagePath("outlined/icon_sysa.svg");

        if ($this->showDevTool()) {
            // position should be 0, see bug #26794
            $item = $mb->topLegacyItem($this->getId())
                        ->withLegacyContent(

                            $DIC->ui()->factory()->legacy($this->getContent())
                        )
                       ->withSymbol($f->symbol()->glyph()->settings())
                       ->withTitle($title)
                       ->withPosition(0);

            return [$item];
        }

        return [];
    }


    /**
     * Show help tool?
     *
     * @param
     *
     * @return
     */
    protected function showDevTool() : bool
    {
        return true;

    }

    /**
     * Get content
     * @return string
     * @throws \ilTemplateException
     */
    protected function getContent()
    {
        global $DIC;

        $ilCtrl = $DIC->ctrl();
        $ilDB = $DIC->database();

        $component_repository = $DIC["component.factory"] ?? null;

        /** @var \ilPlugin $plugin */
        if (!is_null($component_repository)) {
            $plugin = $component_repository->getPlugin($this->getPluginID());
        } else {
            $plugin = $DIC["ilPluginAdmin"]->getPluginObjectById($this->getPluginID());
        }

        $ftpl = $plugin->getTemplate("tpl.dev_info.html");

        // execution time
        $t1 = explode(" ", $GLOBALS['ilGlobalStartTime']);
        $t2 = explode(" ", microtime());
        $diff = $t2[0] - $t1[0] + $t2[1] - $t1[1];

        $mem_usage = array();
        if (function_exists("memory_get_usage")) {
            $mem_usage[] =
                "Memory Usage: " . memory_get_usage() . " Bytes";
        }
        if (function_exists("xdebug_peak_memory_usage")) {
            $mem_usage[] =
                "XDebug Peak Memory Usage: " . xdebug_peak_memory_usage() . " Bytes";
        }
        $mem_usage[] = round($diff, 4) . " Seconds";

        if (sizeof($mem_usage)) {
            $ftpl->setVariable("MEMORY_USAGE", "<br>" . implode(" | ", $mem_usage));
        }


        // controller history
        if (is_object($ilCtrl) && $ftpl->blockExists("c_entry") &&
            $ftpl->blockExists("call_history")) {
            $hist = $ilCtrl->getCallHistory();
            foreach ($hist as $entry) {
                $class = $entry["class"] ?? $entry["cmdClass"];
                $mode = $entry["mode"] ?? $entry["cmdMode"];
                if ($mode == "execComm") {
                    $mode = "executeCommand";
                }
                $cmd = $entry["cmd"];


                $ftpl->setCurrentBlock("c_entry");
                if (is_object($ilDB)) {
                    $file = $ilCtrl->lookupClassPath($class);
                    $file = str_replace($class, "<b>$class</b>", $file);
                    $ftpl->setVariable("FILE", $file);
                } else {
                    $ftpl->setVariable("FILE", $class);
                }
                if (strtolower($ilCtrl->getCmdClass()) == strtolower($class)) {
                    $mode.= " -> ". "<b>".$cmd."</b>";
                }
                $ftpl->setVariable("MODE", $mode);
                $ftpl->parseCurrentBlock();
            }
            $ftpl->setCurrentBlock("call_history");
            $ftpl->parseCurrentBlock();

        }

        // included files
        if (is_object($ilCtrl) && $ftpl->blockExists("i_entry") &&
            $ftpl->blockExists("included_files")) {
            $fs = get_included_files();
            $ifiles = array();
            $total = 0;
            foreach ($fs as $f) {
                $ifiles[] = array("file" => $f, "size" => filesize($f));
                $total += filesize($f);
            }
            if (method_exists(\ilUtil::class, "sortArray")) {
                $ifiles = \ilUtil::sortArray($ifiles, "size", "desc", true);
            } else {
                $ifiles = \ilArrayUtil::sortArray($ifiles, "size", "desc", true);
            }
            foreach ($ifiles as $f) {
                $ftpl->setCurrentBlock("i_entry");
                $ftpl->setVariable("I_ENTRY", $this->getFilePresentation($f["file"]) . " (" . $f["size"] . " Bytes, " . round(100 / $total * $f["size"], 2) . "%)");
                $ftpl->parseCurrentBlock();
            }
            $ftpl->setCurrentBlock("i_entry");
            $ftpl->setVariable("I_ENTRY", "Total (" . $total . " Bytes, 100%)");
            $ftpl->parseCurrentBlock();
            $ftpl->setCurrentBlock("included_files");
            $ftpl->parseCurrentBlock();
        }

        return $ftpl->get();
    }

    protected function getFilePresentation($file)
    {
        if (str_starts_with($file, ILIAS_ABSOLUTE_PATH)) {
            return substr($file, strlen(ILIAS_ABSOLUTE_PATH) + 1);
        }
        return $file;
    }

}

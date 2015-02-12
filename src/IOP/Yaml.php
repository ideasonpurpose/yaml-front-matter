<?php

namespace IOP;

use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Filesystem\Filesystem;
use Michelf\MarkdownExtra;
use Michelf\SmartyPants;

class Yaml extends \Symfony\Component\Yaml\Yaml
{
    public static function parse($path, $exceptionOnInvalidType = false, $objectSupport = false)
    {
        $fs = new Filesystem();
        $relpath = $fs->makePathRelative($path, getcwd());
        $file = new SplFileInfo($path, dirname($relpath), basename($relpath));
        $content = [];

        $fileparts = preg_split('/^---\s*$/m', $file->getContents(), 2, PREG_SPLIT_NO_EMPTY);

        try {
            $content = parent::parse($fileparts[0], $exceptionOnInvalidType, $objectSupport);
            if (count($fileparts) > 1) {
                $content['body'] = $fileparts[1];
            }
        } catch (\Exception $e) {
            // Failed to parse as Yaml, try the whole file as Markdown
            $content['body'] = $file->getContents();
        }

        if (array_key_exists('body', $content)) {
            $content['body'] = MarkdownExtra::defaultTransform($content['body']);
            $content['body'] = SmartyPants::defaultTransform($content['body'], 3);
        }
        if (array_key_exists('callout', $content)) {
            $content['callout'] = MarkdownExtra::defaultTransform($content['callout']);
            $content['callout'] = SmartyPants::defaultTransform($content['callout'], 3);
        }

        $boilerplate = array(
            'slug' => pathinfo($file)['filename'],
            'yaml_source_file' => $file->getRealPath()
            );

        $content = array_merge($boilerplate, $content);
        array_walk_recursive($content, function (&$str) {
            $str = (is_string($str)) ? trim($str) : $str;
        });

        return $content;
    }
}

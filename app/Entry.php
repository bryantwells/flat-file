<?php

use Symfony\Component\Yaml\Yaml;

class Entry {

    function __construct($title, $path, $structure) {
        $segments = explode('/', $path);
        array_shift($segments);

        $this->title = $title;
        $this->path = $path;
        $this->uri = '/' . implode('/', $segments);
        $this->init($structure);
    }

    function init($structure = []) {

        // get schema of the entry
        $schema = array_key_exists('schema', $structure)
            ? $structure['schema']
            : [];

        // get template of the entry
        $this->template = array_key_exists('template', $structure)
            ? $structure['template']
            : '';

        // if the entry has any meta information...
        if (file_exists($this->path . '/_meta.yml')) {
            $meta = Yaml::parseFile($this->path . '/_meta.yml');
            $this->parseMeta($meta);
        }

        // parse the files in the entry
        $this->parseEntry($schema);

        // get list of child directories and schema
        $childDirList = glob($this->path . '/[0-9]*', GLOB_ONLYDIR);
        $childStructure = array_key_exists('children', $structure)
            ? $structure['children']
            : [];

        // if the entry has children
        if ($childDirList && count($childDirList) > 0) {
            $this->parseChildren($childDirList, $childStructure);
        }

    }

    function parseMeta($meta) {

        // add each meta item as a property on the collection object
        foreach($meta as $metaItemKey => $metaItemValue) {
            $this->$metaItemKey = $metaItemValue;
        }
    }

    function parseEntry($schema) {

        // parse entry based on given schema
        foreach($schema as $schemaItemKey => $schemaItemValue) {

            // get file paths based on the item's associated file extensions
            // e.g.: images: ['jpg', 'jpeg']
            $fileList = [];
            foreach($schemaItemValue as $extension) {
                $fileList = array_merge($fileList, glob($this->path . '/*.' . $extension));
            }
            sort($fileList);
            $this->$schemaItemKey = $fileList;

            // if neccessary, parse files
            foreach($this->$schemaItemKey as $fileKey => $fileValue) {

                // init content array
                $this->$schemaItemKey[$fileKey] = [];

                // title
                $segments = explode('/', $fileValue);
                $title = end($segments);
                $this->$schemaItemKey[$fileKey]['title'] = $title;

                // path
                $this->$schemaItemKey[$fileKey]['path'] = $fileValue;

                // extension
                $extension = pathinfo($fileValue)['extension'];
                $this->$schemaItemKey[$fileKey]['extension'] = $extension;
                
                if ($extension == 'md') {

                    // markdown
                    $contents = file_get_contents($fileValue);
                    $html = Parsedown::instance()->text($contents);
                    $this->$schemaItemKey[$fileKey]['parsed'] = $html;
                } else if ($extension == 'txt') {

                    // text file
                    $contents = file_get_contents($fileValue);
                    $this->$schemaItemKey[$fileKey]['parsed'] = $contents;
                }
            }
        }
    }

    function parseChildren($dirList, $structure = []) {

        // add a new Entry object for each subdirectory
        foreach($dirList as $dir) {

            // init title variable
            $title;

            if (file_exists($dir . '/_meta.yml') && Yaml::parseFile($dir . '/_meta.yml')['title']) {

                // title from _meta.yml
                $title = Yaml::parseFile($dir . '/_meta.yml')['title'];
            } else {

                // title from folder name
                $segments = explode('/', $dir);
                $title = end($segments);
            }

            // create new entry
            $this->children[$title] = new Entry($title, $dir, $structure);

            // register route
            Router::registerRoute($this->children[$title]);
        }
    }

}

<?php

// fake the server location for error reporting
$_SERVER['HTTP_HOST'] = 'ideasonpurpose.com';

require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/../lib/IOPYaml.php';

class Framework_YamlTests extends PHPUnit_Framework_TestCase
{
    protected $yaml_files;

    public function setUp()
    {
      // $this->yaml = new Yaml();
      $this->timing_iterations = 200;

      $one = Array ('title'=> 'YAML test file',
                         'slug' => 'one',
                         'array' => array('red', 'blue', 'green', 'yellow'),
                         'yaml_source_file' => __DIR__ . '/yaml/one.yaml');

      $two = Array ('title'=> 'YAML test file',
                         'slug' => 'two',
                         'array' => array('up', 'down', 'left', 'right'),
                         'yaml_source_file' => __DIR__ . '/yaml/two.yaml');

      $three = Array ('title'=> 'YAML test file 3',
                      'slug' => 'three',
                      'array' => array('cow', 'goat', 'pig', 'chicken'),
                      'yaml_source_file' => __DIR__ . '/yaml/subfolder/three.yaml');


      $this->yaml_files = (object) array('one'=>(object) array('file'=>'/yaml/one.yaml', 'content'=>$one),
                                         'two'=>(object) array('file'=>'/yaml/two.yaml', 'content'=>$two),
                                         'three'=>(object) array('file'=>'/yaml/subfolder/three.yaml', 'content'=>$three)
                                        );
      $this->yaml_tree = array('one' => $this->yaml_files->one->content, 
                               'subfolder' => array(
                                                    'three' => $this->yaml_files->three->content, 
                                                    'pages' => array($this->yaml_files->three->content)
                                                    ),
                               'two' => $this->yaml_files->two->content,
                                'pages' => array($this->yaml_files->one->content, $this->yaml_files->two->content));
    }

    public function testLoadYaml_oldway()
    {
      $yaml = $this->yaml_files->one;
      $expected = $yaml->content;
      $actual = load_yaml( __DIR__ . $yaml->file);
      $this->assertEquals($expected, $actual);
    }

    public function testLoadYaml_oldway_looped()
    {
      $yaml = $this->yaml_files->one;
      for ($i=1; $i < $this->timing_iterations; $i++) {
        load_yaml( dirname(__FILE__) . $yaml->file);
      }
      $this->assertTrue(True);
    }

    public function testYamlObjectLoadMethod()
    {
      $yaml = $this->yaml_files->one;
      $expected = $yaml->content;
      $actual = IOPYaml::load( dirname(__FILE__) . $yaml->file);
      $this->assertEquals($expected, $actual);
    }

    public function testYamlObjectLoadMethod_looped()
    {
      $yaml = $this->yaml_files->one;
      for ($i=1; $i < $this->timing_iterations; $i++) {
        IOPYaml::load( __DIR__ . $yaml->file);
      }
      $this->assertTrue(True);
    }

    public function testGetYamlFilesInPath()
    {
      $expected = array($this->yaml_files->one->content, $this->yaml_files->two->content);
      $actual = getYamlFilesInPath( 'app/Tests/yaml');
      $this->assertEquals($expected, $actual);
    }

    public function testLoadYamlAddsSlug()
    {
      $yaml = load_yaml(  dirname(__FILE__) . $this->yaml_files->one->file);
      $this->assertArrayHasKey('slug', $yaml);
    }

    public function test_loadFilesInPath()
    {
      $expected = array($this->yaml_files->one->content, $this->yaml_files->two->content);
      $actual = IOPYaml::loadFilesInPath(__DIR__ . '/yaml');
      $this->assertEquals($expected, $actual);
    }

    public function test_getYamlFilesInPath_looped()
    {
      for ($i=1; $i < $this->timing_iterations; $i++) {
        getYamlFilesInPath( 'app/Tests/yaml');
      }
      $this->assertTrue(True);

    }

    public function test_loadFilesInPath_looped()
    {
      for ($i=1; $i < $this->timing_iterations; $i++) {
        IOPYaml::loadFilesInPath(__DIR__ . '/yaml');
      }
      $this->assertTrue(True);

    }

    public function test_loading_methods()
    {
      $expected = getYamlFilesInPath( 'app/Tests/yaml');
      $actual = IOPYaml::loadFilesInPath(__DIR__ . '/yaml');
      $this->assertEquals($expected, $actual);
    }

    public function test_yaml_loadTree()
    {
      $expected = $this->yaml_tree;
      $actual = IOPYaml::loadTree(__DIR__ . '/yaml');
      $this->assertEquals($expected, $actual);
    }

  }



?>
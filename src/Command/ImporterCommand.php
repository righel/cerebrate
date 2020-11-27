<?php
namespace App\Command;

use Cake\Console\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Filesystem\File;
use Cake\Utility\Hash;
use Cake\Utility\Text;
use Cake\Validation\Validator;
use Cake\Http\Client;

class ImporterCommand extends Command
{
    protected $modelClass = 'Organisations';

    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser->setDescription('Import data based on the provided configuration file.');
        $parser->addArgument('config', [
            'help' => 'Configuration file path for the importer',
            'required' => true
        ]);
        $parser->addArgument('source', [
            'help' => 'The source that should be imported. Can be either a file on the disk or an valid URL.',
            'required' => true
        ]);
        $parser->addArgument('primary_key', [
            'help' => 'To avoid duplicates, entries having the value specified by the primary key will be updated instead of inserted. Empty if only insertion should be done',
            'required' => false,
            'default' => null,
        ]);
        return $parser;
    }

    public function execute(Arguments $args, ConsoleIo $io)
    {
        $this->io = $io;
        $configPath = $args->getArgument('config');
        $source = $args->getArgument('source');
        $primary_key = $args->getArgument('primary_key');

        $table = $this->modelClass;
        $this->loadModel($table);
        // $orgs = $this->{$table}->find()->enableHydration(false)->toList();
        $config = $this->getConfigFromFile($configPath);
        $sourceData = $this->getDataFromSource($source);
        // $sourceData = ['data' => [['name' => 'Test2', 'uuid' => '28de34ac-e284-495c-ae0e-9f46dd12da35', 'sector' => 'ss']]];
        $data = $this->extractData($this->{$table}, $config, $sourceData);
        $entities = $this->marshalData($this->{$table}, $data, $primary_key);
        $entitiesSample = array_slice($entities, 0, min(10, count($entities)));
        $ioTable = $this->transformEntitiesIntoTable($entitiesSample);
        $io->helper('Table')->output($ioTable);
        $selection = $io->askChoice('A sample of the data you are about to save is provided above. Would you like to proceed?', ['Y', 'N'], 'N');
        // debug(json_encode($entities, JSON_PRETTY_PRINT));
        if ($selection == 'Y') {
            $this->saveData($this->{$table}, $entities);
        }
    }

    private function marshalData($table, $data, $primary_key=null)
    {
        $entities = [];
        if (is_null($primary_key)) {
            $entities = $table->newEntities($data);
        } else {
            foreach ($data as $i => $item) {
                $query = $table->find('all')
                    ->where(["${primary_key}" => $item[$primary_key]]);
                $entity = $query->first();
                if ($entity) {
                    $entity->setAccess('uuid', false);
                } else {
                    $entity = $table->newEmptyEntity();
                }
                $entity = $table->patchEntity($entity, $item);
                $entities[] = $entity;
            }
        }
        $hasErrors = false;
        foreach ($entities as $entity) {
            if ($entity->hasErrors()) {
                $hasErrors = true;
                $this->io->error(json_encode(['entity' => $entity, 'errors' => $entity->getErrors()], JSON_PRETTY_PRINT));
            }
        }
        if (!$hasErrors) {
            $this->io->verbose('No validation errors');
        }
        return $entities;
    }

    private function saveData($table, $entities)
    {
        $this->io->verbose('Saving data');
        $result = $table->saveMany($entities);
        if ($result == false) {
            $this->io->error('Error while saving data');
        }
        // take care of meta fields
    }
    
    private function extractData($table, $config, $source)
    {
        $defaultFields = array_flip($table->getSchema()->columns());
        $this->io->verbose('Extracting data');
        $data = [];
        foreach ($config as $key => $fieldConfig) {
            $values = null;
            if (!empty($fieldConfig['path'])) {
                $values = Hash::extract($source, $fieldConfig['path']);
            }
            if (!empty($fieldConfig['massage'])) {
                $values = array_map("self::{$fieldConfig['massage']}", $values);
            }
            if (isset($defaultFields[$key])) {
                $data[$key] = $values;
            } else {
                $data['metaFields'][$key] = $values;
            }
        }
        return $this->invertArray($data);
    }

    private function getDataFromSource($source)
    {
        $data = $this->getDataFromFile($source);
        if ($data === false) {
            $data = $this->getDataFromURL($source);
        }
        return $data;
    }

    private function getDataFromURL($url)
    {
        $validator = new Validator();
        $validator
            ->requirePresence('url')
            ->notEmptyString('url', 'Please provide a valid source')
            ->url('url');
        $errors = $validator->validate(['url' => $url]);
        if (!empty($errors)) {
            $this->io->error(json_encode(Hash::extract($errors, '{s}'), JSON_PRETTY_PRINT));
            die(1);
        }
        $http = new Client();
        $this->io->verbose('Downloading file');
        $response = $http->get($url);
        return $response->getJson();
    }

    private function getDataFromFile($path)
    {
        $file = new File($path);
        if ($file->exists()) {
            $this->io->verbose('Reading file');
            $data = $file->read();
            $file->close();
            if (!empty($data)) {
                $data = json_decode($data, true);
                if (is_null($data)) {
                    $this->io->error('Error while parsing the source file');
                    die(1);
                }
                return $data;
            }
        }
        return false;
    }

    private function getConfigFromFile($configPath)
    {
        $file = new File($configPath);
        if ($file->exists()) {
            $config = $file->read();
            $file->close();
            if (!empty($config)) {
                $config = json_decode($config, true);
                if (is_null($config)) {
                    $this->io->error('Error while parsing the configuration file');
                    die(1);
                }
                return $config;
            } else {
                $this->io->error('Configuration file cound not be read');
            }
        } else {
            $this->io->error('Configuration file not found');
        }
    }

    private function transformResultSetsIntoTable($result, $header=[])
    {
        $table = [[]];
        if (!empty($result)) {
            $tableHeader = empty($header) ? array_keys($result[0]) : $header;
            $tableContent = [];
            foreach ($result as $item) {
                if (empty($header)) {
                    $tableContent[] = array_map('strval', array_values($item));
                } else {
                    $row = [];
                    foreach ($tableHeader as $key) {
                        $row[] = (string) $item[$key];
                    }
                    $tableContent[] = $row;
                }
            }
            $table = array_merge([$tableHeader], $tableContent);
        }
        return $table;
    }

    private function transformEntitiesIntoTable($entities, $header=[])
    {
        $table = [[]];
        if (!empty($entities)) {
            // $tableHeader = empty($header) ? array_keys($entities[0]->toArray()) : $header;
            $tableHeader = empty($header) ? array_keys(Hash::flatten($entities[0]->toArray())) : $header;
            $tableContent = [];
            foreach ($entities as $entity) {
                $row = [];
                foreach ($tableHeader as $key) {
                    $subKeys = explode('.', $key);
                    if (in_array('metaFields', $subKeys)) {
                        $row[] = (string) Hash::get($entity, $key);
                    } else {
                        $row[] = (string) $entity[$key];
                    }
                }
                $tableContent[] = $row;
            }
            $table = array_merge([$tableHeader], $tableContent);
        }
        return $table;
    }

    private function invertArray($data)
    {
        $inverted = [];
        foreach ($data as $key => $values) {
            if ($key == 'metaFields') {
                foreach ($values as $metaKey => $metaValues) {
                    foreach ($metaValues as $i => $metaValue) {
                        $inverted[$i]['metaFields'][$metaKey] = $metaValue;
                    }
                }
            } else {
                foreach ($values as $i => $value) {
                    $inverted[$i][$key] = $value;
                }
            }
        }
        return $inverted;
    }

    private function genUUID($value)
    {
        return Text::uuid();
    }
}
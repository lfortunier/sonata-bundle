<?php

namespace Smart\SonataBundle\Manager;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Smart\SonataBundle\Form\Type\Admin\AbstractImportType;
use Smart\SonataBundle\Utils\ExportImportUtils;
use Smart\SonataBundle\Utils\StringUtils;

class ImportManager
{
    /** @var EntityManagerInterface */
    protected $entityManager;

    /** @var EntityRepository */
    private $repository;

    /** @var string */
    private $importEntityClass;

    /** @var AbstractImportType */
    private $importType;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Construct data for import preview
     * @param array<mixed> $datas
     * @return array<mixed>
     */
    final public function getImportPreviewData(array $datas): array
    {
        $toReturn = [];
        $index = 0;
        $dataCount = count($datas);
        foreach ($datas as $row) {
            // get old entity
            $newEntity = null;
            $oldEntity = $this->getEntity($row);
            if ($oldEntity != null) {
                $newEntity = clone $oldEntity;
            } else {
                $newEntity = new $this->importEntityClass();
            }

            // construct new Entity with import data
            $newEntity = $this->updateEntity($row, $newEntity, $this->importType->getEntityColumsDatas(), $dataCount, $index);
            $isUpdate = false;
            $rowData = [];
            // create preview data from comparaison between old and new entity
            foreach ($this->importType->getEntityColumsDatas() as $columnDatas) {
                $this->managePreviewAttribut($rowData, $isUpdate, $columnDatas, $oldEntity, $newEntity);
            }
            $this->managePreviewAction($rowData, $isUpdate, $oldEntity);
            $toReturn[] = $rowData;
            $index++;
        }
        return $toReturn;
    }

    /**
     * Return result of import
     * @param array<mixed> $datas
     * @return array<mixed>
     * @throws \Doctrine\DBAL\ConnectionException
     */
    final public function import(array $datas): array
    {
        $this->entityManager->getConnection()->beginTransaction();

        $toReturn = [
            '{nb_create}' => 0,
            '{nb_update}' => 0,
        ];

        try {
            $index = 0;
            $dataCount = count($datas);
            foreach ($datas as $key => $row) {
                $entity = $this->getEntity($row);
                if ($entity == null) {
                    $entity = new $this->importEntityClass();
                    $this->entityManager->persist($entity);
                    $toReturn['{nb_create}']++;
                } else {
                    $toReturn['{nb_update}']++;
                }

                $this->updateEntity($row, $entity, $this->importType->getEntityColumsDatas(), $dataCount, $index);
                $this->entityManager->flush();
                $index++;
            }

            $this->entityManager->clear();
            $this->entityManager->getConnection()->commit();
        } catch (\Exception $e) {
            $this->entityManager->getConnection()->rollBack();

            throw $e;
        }

        return $toReturn;
    }

    /**
     * @param ?string $value
     * @param array<mixed> $columnDatas
     * @return false|string|string[]|null
     */
    private function getTransformedValue(?string $value, array $columnDatas)
    {
        if ($value == null) {
            return null;
        }
        $transformedValue = $value;
        $type = $this->getColumnDataType($columnDatas);
        if ($type === 'array') {
            $transformedValue = ExportImportUtils::importSimpleArray($value);
        } elseif ($type === 'string') {
            $transformedValue = ExportImportUtils::transformTextToExportImport($value, true);
        } elseif ($type === 'int') {
            $transformedValue = (int)$value;
        } elseif ($type === 'float') {
            $transformedValue = (float)$value;
        }

        return $transformedValue;
    }

    /**
     * get value of $attributName in $entity
     * @param string $attributName
     * @param Object $entity
     * @return ?Object
     */
    private function getEntityValue(string $attributName, $entity)
    {
        if ($entity == null) {
            return null;
        }

        if (method_exists($entity, "get$attributName")) {
            return $entity->{"get$attributName"}();
        }

        if (method_exists($entity, "is$attributName")) {
            return $entity->{"is$attributName"}();
        }

        if (method_exists($entity, "has$attributName")) {
            return $entity->{"has$attributName"}();
        }

        return new \BadFunctionCallException("no method with $attributName exist");
    }

    /**
     * set $attributName $value to $entity
     * @param string $attributName
     * @param Object $entity
     * @param mixed $value
     */
    private function setEntityValue(string $attributName, $entity, $value): void
    {
        if ($entity != null) {
            $entity->{"set$attributName"}($value);
        }
    }

    /**
     * Manage data preview for Entity
     *
     * @param array<mixed> $rowPreview
     * @param bool $isUpdate
     * @param array<mixed> $columDatas
     * @param object|null $oldEntity
     * @param object $newEntity
     */
    private function managePreviewAttribut(array &$rowPreview, bool &$isUpdate, array $columDatas, ?object $oldEntity, object $newEntity): void
    {
        $columnDataName = $this->getColumnDataName($columDatas);
        $oldValue = null;
        if ($oldEntity != null) {
            $oldValue = $this->getEntityValue(StringUtils::convertToCamelCase($columnDataName, true), $oldEntity);
        }
        $newValue = $this->getEntityValue(StringUtils::convertToCamelCase($columnDataName, true), $newEntity);
        $isChange = $oldValue != $newValue;
        $rowPreview[$columnDataName] = [
            'before' => $oldValue,
            'after' => $newValue,
            'will_change' => $isChange,
        ];

        if ($isChange) {
            $isUpdate = true;
        }
    }

    /**
     * Add action key to $rowPreview
     *
     * @param array<mixed> $rowPreview
     * @param bool $isUpdate
     * @param Object $entity
     */
    private function managePreviewAction(array &$rowPreview, bool $isUpdate, $entity): void
    {
        $importAction = 'import.create';
        if ($entity != null) {
            $importAction = 'import.same';
            if ($isUpdate) {
                $importAction = 'import.update';
            }
        }

        $rowPreview['action'] = $importAction;
    }

    /**
     * Update $entity
     *
     * @param array<mixed> $row
     * @param object $entity
     * @param array<mixed> $columnsDatas
     * @param int $number
     * @param int $index
     * @return object
     */
    public function updateEntity(array $row, object $entity, array $columnsDatas, int $number, int $index): object
    {
        foreach ($columnsDatas as $columDatas) {
            $this->setDefaultEntityValue($row, $columDatas, $entity);
        }

        return $entity;
    }

    /**
     * Get Entity if exist, null if not
     *
     * @param array<mixed> $row
     * @return object|null
     */
    private function getEntity(array $row): ?object
    {
        $entityIdentifierName = $this->getColumnDataName($this->importType->getEntityColumsDatas()[array_search(true, $this->importType->getEntityColumsDatas())]);
        $camelCaseEntityIdentifierName = StringUtils::convertToCamelCase($entityIdentifierName);
        $entityIdentifierValue = $row[$entityIdentifierName];
        $entity = $this->repository->findOneBy([$camelCaseEntityIdentifierName => $entityIdentifierValue]);
        if ($entity != null) {
            $this->setEntityValue($camelCaseEntityIdentifierName, $entity, $entityIdentifierValue);
        }

        return $entity;
    }

    /**
     * Set the Repository used for import
     */
    private function setRepository(): void
    {
        if (!$this->repository instanceof EntityRepository) {
            //@phpstan-ignore-next-line
            $this->repository = $this->entityManager->getRepository($this->importEntityClass);
        }
    }

    /**
     * Set the EntityClass used for import
     *
     * @param string $importEntityClass
     */
    final public function setImportEntityClass(string $importEntityClass): void
    {
        $this->importEntityClass = $importEntityClass;
        $this->setRepository();
    }

    /**
     * Set value of $columnDatas['name'] in $entity
     * Can call method if same $name is present in $row and in $columnDatas
     *
     * @param array<mixed> $row
     * @param array<mixed> $columnDatas
     * @param object $entity
     */
    final public function setDefaultEntityValue(array $row, array $columnDatas, object $entity): void
    {
        $name = $columnDatas['name'];
        $transformedValue = $this->getTransformedValue($row[$name], $columnDatas);
        $camelCaseName = StringUtils::convertToCamelCase($name);

        $this->setEntityValue($camelCaseName, $entity, $transformedValue);
    }

    /**
     * @param array<mixed> $columnDatas
     * @return string
     */
    private function getColumnDataType(array $columnDatas): string
    {
        return $columnDatas['type'];
    }

    /**
     * @param array<mixed> $columnDatas
     * @return string
     */
    private function getColumnDataName(array $columnDatas): string
    {
        return $columnDatas['name'];
    }

    /**
     * Set ImportType
     *
     * @param AbstractImportType $importType
     */
    final public function setImportType(AbstractImportType $importType): void
    {
        $this->importType = $importType;
    }
}

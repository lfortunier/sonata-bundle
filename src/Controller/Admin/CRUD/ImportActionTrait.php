<?php

namespace Smart\SonataBundle\Controller\Admin\CRUD;

use Smart\SonataBundle\Exception\Utils\ArrayUtils\MultiArrayNbColumnsException;
use Smart\SonataBundle\Exception\Utils\ArrayUtils\MultiArrayNbMaxRowsException;
use Smart\SonataBundle\Form\Type\Admin\AbstractImportType;
use Smart\SonataBundle\Manager\ImportManager;
use Smart\SonataBundle\Utils\ArrayUtils;
use Smart\SonataBundle\Utils\StringUtils;
use Symfony\Component\HttpFoundation\Request;

trait ImportActionTrait
{
    /**
     * Insert/Modification multiple
     */
    public function importable(
        Request $request,
        AbstractImportType $abstractImportType,
        ?ImportManager $importManager = null
    ) {
        $form = $this->createForm(get_class($abstractImportType));
        $form->handleRequest($request);
        $showImportPreview = false;
        $importPreviewData = null;
        $snakeCaseEntityName = StringUtils::convertToSnakeCase($this->admin->getClassnameLabel());
        $entitiesLabel = $this->trans("import.label_$snakeCaseEntityName");
        if ($importManager == null) {
            $importManager = $this->get('app.manager.import');
        }
        $importManager->setImportEntityClass($this->admin->getClass());

        if ($form->isSubmitted() and $form->isValid()) {
            $importManager->setImportType($abstractImportType);
            try {
                $rawData = $form->getData()['raw_data'];
                $importData = ArrayUtils::getMultiArrayFromTextarea(
                    $rawData,
                    $abstractImportType::DELIMITER_TEXTAREA,
                    $abstractImportType::getImportColums(),
                    $abstractImportType::NB_MAX_ROWS
                );
                //@phpstan-ignore-next-line
                if ($form->get('import_preview')->isClicked()) {
                    $showImportPreview = true;
                    $importPreviewData = [
                        'data' => $importManager->getImportPreviewData($importData),
                        'header' => $abstractImportType::getEntityColumsDatas()
                    ];
                    // @phpstan-ignore-next-line
                } elseif ($form->get('import')->isClicked()) {
                    $importNbs = $importManager->import($importData);
                    $importNbs['{nb_skip}'] = StringUtils::getNbRowsFromTextarea($rawData)
                        - $importNbs['{nb_create}']
                        - $importNbs['{nb_update}'];
                    $importNbs['{entity_label}'] = $entitiesLabel;
                    $this->addFlash('sonata_flash_success', $this->trans('import.label_success', $importNbs));

                    return $this->redirectToList();
                } // else 'cancel_import' we do nothing to retrun on form without data loose
            } catch (MultiArrayNbMaxRowsException $e) {
                $this->addFlash('sonata_flash_error', $this->trans($e->getMessage(), [
                    '{nbMaxRows}' => $e->nbMaxRows,
                    '{nbRows}' => $e->nbRows,
                ], 'validators'));
            } catch (MultiArrayNbColumnsException $e) {
                $this->addFlash('sonata_flash_error', $this->trans($e->getMessage(), [
                    '{keys}' => implode(", ", $e->keys),
                ], 'validators'));
            } catch (\Exception $e) {
                $this->addFlash('sonata_flash_error', $this->trans("import.error", ['{entity_label}' => $entitiesLabel]) . $e->getMessage());
            }
        }

        return $this->renderWithExtraParams('@SmartSonata/import/import.html.twig', [
            'form' => $form->createView(),
            'admin' => $this->admin,
            'breadcrumb_title' => $this->trans('breadcrumb.link_' . $snakeCaseEntityName . '_list'),
            'title' => $this->trans('import.label_title', ['{entity_label}' => $entitiesLabel]),
            'form_help' => $this->trans('field.help_import', [
                '{columns}' => implode($abstractImportType::DELIMITER_TEXTAREA . " ", $abstractImportType::getImportColums()),
                '{nb}' => $abstractImportType::NB_MAX_ROWS,
            ]),
            'show_import_preview' => $showImportPreview,
            'import_preview_data' => $importPreviewData,
            'import_preview_template' => "@SmartSonata/import/import_preview.html.twig",
        ]);
    }
}

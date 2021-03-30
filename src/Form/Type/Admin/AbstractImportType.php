<?php

namespace Smart\SonataBundle\Form\Type\Admin;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints\File;

abstract class AbstractImportType extends AbstractType
{
    const DELIMITER_TEXTAREA = ",";
    const NB_MAX_ROWS = 2000;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * Colums present in the import data
     *
     * @return string[]
     */
    abstract public static function getImportColums(): array;

    /**
     * Set entity parameter to import system
     * Structure :
     *   [
     *      'name' => string, -> Name of the attribut
     *      'isIdentifier' => true|false, -> If the attribut is identifier of entity
     *      'type' => 'string'|'array'|other, -> Used for transform value of import
     *      'width' => int, -> Width of the colum in the import preview
     *      'label' => trad key -> Label of the import preview column
     *   ],
     * @return array<array>
     */
    abstract public static function getEntityColumsDatas(): array;

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('file', FileType::class, [
                'label' => 'field.label_import_file',
                'required' => false,
                'constraints' => [
                    new File([
                        'mimeTypes' => ["text/plain", "text/x-csv", "text/csv"],
                        'maxSize' => "20M",
                    ]),
                ]
            ])
            ->add('textarea', TextareaType::class, [
                'label' => 'field.label_import_textarea',
                'required' => false
            ])
            ->add('raw_data', HiddenType::class, [
                'label' => 'field.label_import_textarea',
                'required' => false
            ])
            ->add('import_preview', SubmitType::class, [
                'label' => 'action.import_preview',
            ])
            ->add('import', SubmitType::class, [
                'label' => 'action.import',
            ])
            ->add('cancel_import', SubmitType::class, [
                'label' => 'action.cancel',
            ]);

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            $form = $event->getForm();

            $fileData = $data['file'];
            $textAreaData = $data['textarea'];
            $rawData = $data['raw_data'];

            // Validation of at least one given data
            if ($fileData == null and trim($textAreaData) == '' and trim($rawData) == '') {
                $form->addError(new FormError(
                    $this->translator->trans('import_type.no_data_error', [], 'validators')
                ));
            }

            // Validation "Only one of the 2 fields must be completed"
            if ($fileData instanceof UploadedFile and trim($textAreaData) != '') {
                $form->addError(new FormError(
                    $this->translator->trans('import_type.not_unique_data_source_error', [], 'validators')
                ));
            }

            if (trim($textAreaData) != '') {
                $rawData = $textAreaData;
            }
            if ($fileData instanceof UploadedFile) {
                $rawData = (string)file_get_contents($fileData->getPathname());
                // To remove unwanted characters due to the csv encoding, for example "ï»¿" at the start of the file
                // https://stackoverflow.com/questions/10290849/how-to-remove-multiple-utf-8-bom-sequences
                $bom = pack('H*', 'EFBBBF');
                $rawData = preg_replace("/^$bom/", '', $rawData);
            }
            $data['raw_data'] = $rawData;

            // Set the raw_data hidden with data from file or textarea
            $event->setData($data);
        });
    }

    /**
     * @inheritdoc
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'translation_domain' => 'admin'
            ]);
    }
}

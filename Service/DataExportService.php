<?php

namespace Ojs\ExportBundle\Service;

use Doctrine\ORM\EntityManager;
use JMS\Serializer\SerializerBuilder;
use Ojs\CoreBundle\Helper\FileHelper;
use Ojs\JournalBundle\Entity\Journal;
use JMS\Serializer\Serializer;
use Ojs\ExportBundle\Entity\DataExport;

/**
 * Class DataExportService
 * @package Ojs\ExportBundle\Service
 */
class DataExportService
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @var Journal
     */
    private $journal = null;

    /**
     * @var string
     */
    private $kernelRootDir;

    /**
     * JournalExportService constructor.
     *
     * @param EntityManager $em
     * @param $kernelRootDir
     */
    public function __construct(EntityManager $em, $kernelRootDir)
    {
        $this->serializer       = SerializerBuilder::create()->build();
        $this->kernelRootDir    = $kernelRootDir;
        $this->em               = $em;
    }

    /**
     * @param Journal $journal
     */
    public function setJournal(Journal $journal)
    {
        $this->journal = $journal;
    }

    /**
     * @return mixed|string
     */
    public function journalToJson()
    {
        if($this->journal === null){
            throw new \LogicException('You must to specify journal param');
        }
        return $this->serializer->serialize($this->journal, 'json');
    }

    /**
     * @return mixed|string
     */
    public function journalToXml()
    {
        if($this->journal === null){
            throw new \LogicException('You must to specify journal param');
        }
        return $this->serializer->serialize($this->journal, 'xml');
    }

    /**
     * @param string $content
     * @param string $type
     * @return string
     */
    public function storeAsFile($content, $type)
    {
        $dataExportDir = $this->kernelRootDir . '/../web/uploads/data_export/';
        $fileHelper = new FileHelper();
        $generatePath = $fileHelper->generateRandomPath();
        if(!is_dir($dataExportDir.$generatePath)){
            mkdir($dataExportDir.$generatePath, 0775, true);
        }
        $filePath = $generatePath . $this->journal->getTranslations()->first()->getTitle().'-'.$this->journal->getId().'.'.$type;
        file_put_contents($dataExportDir.$filePath, $content);

        return $filePath;
    }

    /**
     * @param $filePath
     * @param $type
     */
    public function addToHistory($filePath, $type)
    {
        $dataExport = new DataExport();
        $dataExport
            ->setCreatedAt(new \DateTime())
            ->setPath($filePath)
            ->setType($type)
            ->setJournal($this->journal)
        ;
        $this->em->persist($dataExport);
        $this->em->flush();

        return;
    }
}
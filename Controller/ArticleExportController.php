<?php

namespace Ojs\ExportBundle\Controller;

use APY\DataGridBundle\Grid\Column\ActionsColumn;
use APY\DataGridBundle\Grid\Row;
use APY\DataGridBundle\Grid\Source\Entity;
use Ojs\CoreBundle\Controller\OjsController as Controller;
use Ojs\JournalBundle\Entity\Article;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use APY\DataGridBundle\Grid\Action\MassAction;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class ArticleExportController extends Controller
{
    /**
     * @param Request $request
     * @return Response
     */
    public function indexAction(Request $request)
    {
        $translator = $this->get('translator');
        $grid = $this->get('grid');
        $cache = $this->get('array_cache');
        $journal = $this->get('ojs.journal_service')->getSelectedJournal();
        $source = new Entity(Article::class, 'export');
        $source->manipulateRow(
            function (Row $row) use ($request, $cache) {
                /** @var Article $entity */
                $entity = $row->getEntity();
                if (!is_null($entity)) {
                    $entity->setDefaultLocale($request->getDefaultLocale());
                    if($cache->contains('grid_row_id_'.$entity->getId())){
                        $row->setClass('hidden');
                    }else{
                        $cache->save('grid_row_id_'.$entity->getId(), true);
                        $row->setField('translations.title', $entity->getTitleTranslations());
                        if (!is_null($entity->getIssue())) {
                            $row->setField('issue.translations.title', $entity->getIssue()->getTitleTranslations());
                        }
                    }
                }

                return $row;
            }
        );
        $grid->setSource($source);

        //setup mass actions
        $exportJsonAction = new MassAction($translator->trans('export.as.json'), [
            $this, 'massArticleJson'
        ]);
        $exportXmlAction = new MassAction($translator->trans('export.as.xml'), [
            $this, 'massArticleXml'
        ]);
        $exportCrossrefAction = new MassAction($translator->trans('export.as.crossref'), [
            $this, 'massArticleCrossref'
        ]);
        $grid->addMassAction($exportJsonAction);
        $grid->addMassAction($exportXmlAction);
        $grid->addMassAction($exportCrossrefAction);

        //setup sing article export actions
        $exportGridAction = $this->get('export_grid_action');
        $actionColumn = new ActionsColumn("actions", 'actions');
        $rowAction[] = $exportGridAction->exportSingleArticleAsCrossref($journal->getId());
        $rowAction[] = $exportGridAction->exportSingleArticleAsJson($journal->getId());
        $rowAction[] = $exportGridAction->exportSingleArticleAsXml($journal->getId());
        $actionColumn->setRowActions($rowAction);
        $grid->addColumn($actionColumn);

        return $grid->getGridResponse('OjsExportBundle:ArticleExport:index.html.twig', [
            'grid'      => $grid,
            'journal'   => $journal,
        ]);
    }

    /**
     * @param Request $request
     * @param Article $article
     * @return BinaryFileResponse
     */
    public function singleArticleJsonAction(Request $request, Article $article)
    {
        $dataExport = $this->get('ojs.data_export');
        $dataExport->setJournal($article->getJournal());
        $dataExport->setArticle($article);
        $jsonArticleData = $dataExport->articleToJson();
        $filePath = $dataExport->storeAsFile($jsonArticleData, 'json', $article->getId());
        $explode = explode('/', $filePath);
        $fileName = end($explode);
        $dataExport->addToHistory($filePath, 'json');
        $file = $this->getParameter('kernel.root_dir'). '/../web/uploads/data_export/'.$filePath;
        $response = new BinaryFileResponse($file);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $fileName);

        return $response;
    }

    /**
     * @param $primaryKeys
     * @return BinaryFileResponse
     */
    public function massArticleJson($primaryKeys)
    {
        $em = $this->getDoctrine()->getManager();
        $articleRepo = $em->getRepository(Article::class);
        $journalService = $this->get('ojs.journal_service');
        $dataExport = $this->get('ojs.data_export');
        $dataExport->setJournal($journalService->getSelectedJournal());
        $dataExport->setArticles($articleRepo->findById($primaryKeys));
        $jsonArticlesData = $dataExport->articlesToJson();
        $filePath = $dataExport->storeAsFile($jsonArticlesData, 'json', 'articles');
        $explode = explode('/', $filePath);
        $fileName = end($explode);
        $dataExport->addToHistory($filePath, 'json');
        $file = $this->getParameter('kernel.root_dir'). '/../web/uploads/data_export/'.$filePath;
        $response = new BinaryFileResponse($file);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $fileName);

        return $response;
    }

    /**
     * @param Request $request
     * @param Article $article
     * @return BinaryFileResponse
     */
    public function singleArticleXmlAction(Request $request, Article $article)
    {
        $dataExport = $this->get('ojs.data_export');
        $dataExport->setJournal($article->getJournal());
        $dataExport->setArticle($article);
        $xmlArticleData = $dataExport->articleToXml();
        $filePath = $dataExport->storeAsFile($xmlArticleData, 'xml', $article->getId());
        $explode = explode('/', $filePath);
        $fileName = end($explode);
        $dataExport->addToHistory($filePath, 'xml');
        $file = $this->getParameter('kernel.root_dir'). '/../web/uploads/data_export/'.$filePath;
        $response = new BinaryFileResponse($file);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $fileName);

        return $response;
    }

    /**
     * @param $primaryKeys
     * @return BinaryFileResponse
     */
    public function massArticleXml($primaryKeys)
    {
        $em = $this->getDoctrine()->getManager();
        $articleRepo = $em->getRepository(Article::class);
        $journalService = $this->get('ojs.journal_service');
        $dataExport = $this->get('ojs.data_export');
        $dataExport->setJournal($journalService->getSelectedJournal());
        $dataExport->setArticles($articleRepo->findById($primaryKeys));
        $jsonArticlesData = $dataExport->articlesToXml();
        $filePath = $dataExport->storeAsFile($jsonArticlesData, 'xml', 'articles');
        $explode = explode('/', $filePath);
        $fileName = end($explode);
        $dataExport->addToHistory($filePath, 'xml');
        $file = $this->getParameter('kernel.root_dir'). '/../web/uploads/data_export/'.$filePath;
        $response = new BinaryFileResponse($file);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $fileName);

        return $response;
    }

    /**
     * @param Request $request
     * @param Article $article
     * @return BinaryFileResponse
     */
    public function singleArticleCrossrefAction(Request $request, Article $article)
    {
        $journal = $this->get('ojs.journal_service')->getSelectedJournal();
        $dataExport = $this->get('ojs.data_export');
        $dataExport->setJournal($journal);
        $dataExport->setArticle($article);
        $crossrefCrossrefData = $this->renderView('OjsExportBundle:ArticleExport:crossref.xml.twig', [
            'articles' => [$article],
            'journal' => $journal,
        ]);
        $filePath = $dataExport->storeAsFile($crossrefCrossrefData, 'xml', $article->getId());
        $explode = explode('/', $filePath);
        $fileName = end($explode);
        $dataExport->addToHistory($filePath, 'crossref');
        $file = $this->getParameter('kernel.root_dir'). '/../web/uploads/data_export/'.$filePath;
        $response = new BinaryFileResponse($file);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $fileName);

        return $response;
    }

    /**
     * @param $primaryKeys
     * @return BinaryFileResponse
     */
    public function massArticleCrossref($primaryKeys)
    {
        $em = $this->getDoctrine()->getManager();
        $articleRepo = $em->getRepository(Article::class);
        $journal = $this->get('ojs.journal_service')->getSelectedJournal();
        $dataExport = $this->get('ojs.data_export');
        $dataExport->setJournal($journal);
        $articles = $articleRepo->findById($primaryKeys);
        $crossrefCrossrefData = $this->renderView('OjsExportBundle:ArticleExport:crossref.xml.twig', [
            'articles' => $articles,
            'journal' => $journal,
        ]);
        $filePath = $dataExport->storeAsFile($crossrefCrossrefData, 'xml', 'articles');
        $explode = explode('/', $filePath);
        $fileName = end($explode);
        $dataExport->addToHistory($filePath, 'crossref');
        $file = $this->getParameter('kernel.root_dir'). '/../web/uploads/data_export/'.$filePath;
        $response = new BinaryFileResponse($file);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $fileName);

        return $response;
    }
}

<?php
namespace Shlinkio\Shlink\CLI\Command\Shortcode;

use Acelaya\ZsmAnnotatedServices\Annotation\Inject;
use Shlinkio\Shlink\Common\Paginator\Adapter\PaginableRepositoryAdapter;
use Shlinkio\Shlink\Common\Paginator\Util\PaginatorUtilsTrait;
use Shlinkio\Shlink\Core\Service\ShortUrlService;
use Shlinkio\Shlink\Core\Service\ShortUrlServiceInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Zend\I18n\Translator\TranslatorInterface;

class ListShortcodesCommand extends Command
{
    use PaginatorUtilsTrait;

    /**
     * @var ShortUrlServiceInterface
     */
    private $shortUrlService;
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * ListShortcodesCommand constructor.
     * @param ShortUrlServiceInterface $shortUrlService
     * @param TranslatorInterface $translator
     *
     * @Inject({ShortUrlService::class, "translator"})
     */
    public function __construct(ShortUrlServiceInterface $shortUrlService, TranslatorInterface $translator)
    {
        $this->shortUrlService = $shortUrlService;
        $this->translator = $translator;
        parent::__construct(null);
    }

    public function configure()
    {
        $this->setName('shortcode:list')
             ->setDescription($this->translator->translate('List all short URLs'))
             ->addOption(
                 'page',
                 'p',
                 InputOption::VALUE_OPTIONAL,
                 sprintf(
                     $this->translator->translate('The first page to list (%s items per page)'),
                     PaginableRepositoryAdapter::ITEMS_PER_PAGE
                 ),
                 1
             )
             ->addOption(
                 'searchTerm',
                 's',
                 InputOption::VALUE_OPTIONAL,
                 $this->translator->translate(
                     'A query used to filter results by searching for it on the longUrl and shortCode fields'
                 )
             )
             ->addOption(
                 'tags',
                 't',
                 InputOption::VALUE_OPTIONAL,
                 $this->translator->translate('A comma-separated list of tags to filter results')
             )
             ->addOption(
                 'orderBy',
                 'o',
                 InputOption::VALUE_OPTIONAL,
                 $this->translator->translate(
                     'The field from which we want to order by. Pass ASC or DESC separated by a comma'
                 )
             )
             ->addOption(
                 'showTags',
                 null,
                 InputOption::VALUE_NONE,
                 $this->translator->translate('Whether to display the tags or not')
             );
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $page = intval($input->getOption('page'));
        $searchTerm = $input->getOption('searchTerm');
        $tags = $input->getOption('tags');
        $tags = ! empty($tags) ? explode(',', $tags) : [];
        $showTags = $input->getOption('showTags');
        $orderBy = $input->getOption('orderBy');

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        do {
            $result = $this->shortUrlService->listShortUrls($page, $searchTerm, $tags, $this->processOrderBy($input));
            $page++;
            $table = new Table($output);

            $headers = [
                $this->translator->translate('Short code'),
                $this->translator->translate('Original URL'),
                $this->translator->translate('Date created'),
                $this->translator->translate('Visits count'),
            ];
            if ($showTags) {
                $headers[] = $this->translator->translate('Tags');
            }
            $table->setHeaders($headers);

            foreach ($result as $row) {
                $shortUrl = $row->jsonSerialize();
                if ($showTags) {
                    $shortUrl['tags'] = [];
                    foreach ($row->getTags() as $tag) {
                        $shortUrl['tags'][] = $tag->getName();
                    }
                    $shortUrl['tags'] = implode(', ', $shortUrl['tags']);
                } else {
                    unset($shortUrl['tags']);
                }

                $table->addRow(array_values($shortUrl));
            }
            $table->render();

            if ($this->isLastPage($result)) {
                $continue = false;
                $output->writeln(
                    sprintf('<info>%s</info>', $this->translator->translate('You have reached last page'))
                );
            } else {
                $continue = $helper->ask($input, $output, new ConfirmationQuestion(
                    sprintf('<question>' . $this->translator->translate(
                        'Continue with page'
                    ) . ' <bg=cyan;options=bold>%s</>? (y/N)</question> ', $page),
                    false
                ));
            }
        } while ($continue);
    }

    protected function processOrderBy(InputInterface $input)
    {
        $orderBy = $input->getOption('orderBy');
        if (empty($orderBy)) {
            return null;
        }

        $orderBy = explode(',', $orderBy);
        return count($orderBy) === 1 ? $orderBy[0] : [$orderBy[0] => $orderBy[1]];
    }
}

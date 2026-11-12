<?php

declare(strict_types=1);

namespace Gratlinie\GratReviewsBundle\Command;

use Doctrine\DBAL\Connection;
use Gratlinie\GratReviewsBundle\Sync\ReviewSynchronizer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'gratreviews:sync', description: 'Ruft die Bewertungen der Google-Quellen ab (ohne ID: alle veröffentlichten, unabhängig vom Intervall).')]
class SyncReviewsCommand extends Command
{
    public function __construct(
        private readonly ReviewSynchronizer $synchronizer,
        private readonly Connection $db,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('id', InputArgument::OPTIONAL, 'ID der Quelle');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $ids = null !== $input->getArgument('id')
            ? [(int) $input->getArgument('id')]
            : array_map('intval', $this->db->fetchFirstColumn("SELECT id FROM tl_gr_source WHERE type = 'google' AND published = 1"));
        $failed = false;

        foreach ($ids as $id) {
            $result = $this->synchronizer->sync($id);
            $failed = $failed || !$result->success;
            $io->writeln(\sprintf('Quelle %d: %s', $id, $result->message()));
        }

        return $failed ? Command::FAILURE : Command::SUCCESS;
    }
}

<?php

namespace Wpmetcom\AutomatedTranslation\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Console\Style\SymfonyStyle;

use  Wpmetcom\AutomatedTranslation\Services\InputOutput;
use  Wpmetcom\AutomatedTranslation\Services\Helper;


class DefaultCommand extends Command
{
    /**
     * The name of the command (the part after "bin/demo").
     *
     * @var string
     */
    protected static $defaultName = 'translate';

    /**
     * The command description shown when running "php bin/demo list".
     *
     * @var string
     */
    protected static $defaultDescription = 'Batch translation. Input language list for translations! Example: bin/fire translate ar fr';

    /**
     * Execute the command
     *
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     * @return int 0 if everything went fine, or an exit code.
     */

    protected $io;
    protected $cwd;
    protected $filesystem;
    protected $finder;
    protected $pkjDir;
    protected $credentialPath;
    protected $pluginHeaders = [];
    protected $languages = [];
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new InputOutput($input, $output);
        $finder   = new Finder;
        $filesystem   = new Filesystem();

        $this->credentialPath = getcwd() . '/credentials.json';
        if (!$filesystem->exists($this->credentialPath)) {
            throw new \RuntimeException('credentials.json does not exist in current directory.');
        }

        $this->languages = $input->getArgument('languages');
        $this->checkLanguageCodes();

        $this->io->note('Selected languages: ' . implode(', ', $this->languages));

        $this->cwd = $this->io->ask('Enter full path of plugin directory.', '', function ($cwd) {

            $cwd = Helper::trim((string) $cwd);

            if (!is_string($cwd) || empty($cwd)) {
                throw new \RuntimeException('Directory can not be empty.');
            }

            $this->generatePluginHeaders($cwd);

            return $cwd;
        });

        $this->io->note('Selected plugin directory: ' . $this->cwd);

        $this->runGoogleTranslateCommand();

        return Command::SUCCESS;
    }

    protected function configure(): void
    {
        $reflector = new \ReflectionClass("\Wpmetcom\AutomatedTranslation\Commands\DefaultCommand");
        $this->pkjDir = Path::getDirectory(Path::getDirectory($reflector->getFileName()) . '/../');

        $this
            // the command help shown when running the command with the "--help" option
            ->setHelp('This command allows you to create a user...');

        $this
            ->addArgument(
                'languages',
                InputArgument::IS_ARRAY | InputArgument::REQUIRED,
                'Enter the target languages that need to be translate (separate multiple languages with a space)?'
            );
    }

    private function runGoogleTranslateCommand()
    {
        if ($this->io->confirm('All OK! Start translating?')) {

            foreach ($this->languages as $lan) {

                $this->io->info('Processing ""' . $lan . '""');

                exec(
                    $this->pkjDir . "/bin/fire google "
                        . $this->pluginHeaders['potPath'] . " "
                        . $this->pluginHeaders['domainPath']
                        . " --credentials=" . $this->credentialPath
                        . " --no-cache --from=en --to=" . $lan
                );
            }
        }
    }

    protected function checkLanguageCodes()
    {
        $languageCodes = [
            'ar', 'bn', 'bg', 'ca', 'zh_CN',
            'zh_TW', 'hr', 'cs', 'da', 'nl',
            'en', 'et', 'tl', 'fi', 'fr',
            'de', 'el', 'gu', 'iw', 'hi',
            'hu', 'is', 'id', 'it', 'ja',
            'kn', 'ko', 'lv', 'lt', 'ms',
            'ml', 'mr', 'no', 'fa', 'pl',
            'pt', 'pa', 'ro', 'ru', 'sr',
            'sk', 'sl', 'es', 'sv', 'ta',
            'te', 'th', 'tr', 'uk', 'ur',
            'vi'
        ];
        $error = false;

        foreach ($this->languages as $lan) {
            if (!in_array($lan, $languageCodes)) {
                $this->io->error('"' . $lan . '" does not exists in https://developers.google.com/google-ads/api/data/codes-formats#languages');
                $error = true;
            }
        }

        if ($error === true) {
            exit();
        }
    }

    private function generatePluginHeaders($cwd): void
    {
        $finder   = new Finder;
        $filesystem   = new Filesystem();

        $finder
            ->files()
            ->in($cwd)
            ->depth('== 0')
            ->name('/\.php$/');

        if (!$finder->hasResults()) {
            $this->pluginHeaders = [];
            throw new \RuntimeException('WP plugin does not exist in ' . $cwd);
        }

        foreach ($finder as $file) {
            $contents = $file->getContents();
            preg_match_all('/Plugin Name\s?:\s?(.*)\s?/', $contents, $pluginName, PREG_SET_ORDER, 0);
            preg_match_all('/Version\s?:\s?(.*)\s?/', $contents, $version, PREG_SET_ORDER, 0);
            preg_match_all('/Text Domain\s?:\s?(.*)\s?/', $contents, $textDomain, PREG_SET_ORDER, 0);
            preg_match_all('/Domain Path\s?:\s?(.*)\s?/', $contents, $domainPath, PREG_SET_ORDER, 0);

            if (!empty($pluginName) && !empty($version)) {
                break;
            }
        }

        $this->pluginHeaders['pluginName'] = Helper::trim($pluginName[0][1]);
        $this->pluginHeaders['version'] = Helper::trim($version[0][1]);

        $this->io->note('Plugin found: ' . $this->pluginHeaders['pluginName'] . ' v' . $this->pluginHeaders['version']);

        $this->pluginHeaders['textDomain'] = Helper::trim((string) $this->io->ask('Enter textdomain:', Helper::trim($textDomain[0][1] ?? '')));

        $this->pluginHeaders['domainPath'] = Helper::trim((string) $this->io->ask('Enter languagePath (default: /languages):', Helper::trim($domainPath[0][1] ?? '/languages')));

        $this->pluginHeaders['domainPath'] = $cwd . $this->pluginHeaders['domainPath'];

        $this->pluginHeaders['potPath'] = $this->pluginHeaders['domainPath'] . '/' . $this->pluginHeaders['textDomain'] . '.pot';

        if (!$filesystem->exists($this->pluginHeaders['potPath'])) {
            throw new \RuntimeException('Language POT file or the directory does not exist in ' . ($this->pluginHeaders['potPath']));
        }
    }
}
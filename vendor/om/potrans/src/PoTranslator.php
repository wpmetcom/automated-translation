<?php

namespace potrans;

use Gettext\Generator\MoGenerator;
use Gettext\Generator\PoGenerator;
use Gettext\Loader\PoLoader;
use Gettext\Translations;
use potrans\translator\Translator;
use Symfony\Component\Cache\Adapter\AdapterInterface;

class PoTranslator {

	/** @var \potrans\translator\Translator */
	private Translator $translator;
	/** @var \Symfony\Component\Cache\Adapter\AdapterInterface */
	private AdapterInterface $cache;
	/** @var \Gettext\Translations */
	private Translations $sentences;

	public function __construct(Translator $translator, AdapterInterface $cache) {
		$this->translator = $translator;
		$this->cache = $cache;
	}

	/**
	 * Read PO file
	 *
	 * @param string $filename
	 * @return \Gettext\Translations
	 */
	public function loadFile(string $filename): Translations {
		return $this->sentences = (new PoLoader())->loadFile($filename);
	}

	/**
	 * Save MO file
	 *
	 * @param string $filename
	 * @return bool
	 */
	public function saveMoFile(string $filename): bool {
		return (new MoGenerator())->generateFile($this->sentences, $filename);
	}

	/**
	 * Save PO file
	 *
	 * @param string $filename
	 * @return bool
	 */
	public function savePoFile(string $filename): bool {
		return (new PoGenerator())->generateFile($this->sentences, $filename);
	}

	/**
	 * Translate all sentences
	 *
	 * @param string $from
	 * @param string $to
	 * @param bool $force
	 * @return \Generator
	 * @throws \Psr\Cache\InvalidArgumentException
	 */
	public function translate(string $from, string $to, bool $force = false): \Generator {
		/** @var \Gettext\Translation $sentence */
		foreach ($this->sentences as $sentence) {

			// sentence translation is missing or we re-translate everything
			if (!$sentence->isTranslated() || $force) {
				$key = md5($sentence->getOriginal() . $from . $to);
				$translation = $this->cache->getItem($key);

				if (!$translation->isHit()) {

					$translation->set(
						$this->translator
							->from($from)
							->to($to)
							->getTranslation($sentence)
					);

					// save cache
					$this->cache->save($translation);
				}

				$sentence->translate($translation->get());

				yield $sentence;
			}
		}
	}
}

<?php

namespace UpAssist\Neos\AssetHelpers\Command;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Persistence\Exception\IllegalObjectTypeException;
use Neos\Media\Browser\Controller\AssetController;
use Neos\Media\Domain\Model\Asset;
use Neos\Media\Domain\Repository\AssetCollectionRepository;
use Neos\Media\Domain\Repository\AssetRepository;

class AssetCommandController extends CommandController
{
    /**
     * @var AssetRepository
     * @Flow\Inject
     */
    protected $assetRepository;

    /**
     * @var AssetCollectionRepository
     * @Flow\Inject
     */
    protected $assetCollectionRepository;

    /**
     * @param string $collectionName
     * @return void
     * @throws IllegalObjectTypeException
     */
    public function addAllAssetsToCollectionCommand(string $collectionName)
    {
        $assets = $this->assetRepository->findAll();
        $collection = $this->assetCollectionRepository->findOneByTitle($collectionName);

        if (empty($collection)) {
            $this->outputLine('The collection with the name %s could not be found', [$collectionName]);
        }
        if (empty($assets)) {
            $this->outputLine('Something must be wrong or you have no assets uploaded yet: assets could not be found');
        }
        if ($assets && $collection) {
            /** @var Asset $asset */
            foreach ($assets as $asset) {
                if ($collection->addAsset($asset)) {
                    $this->assetCollectionRepository->update($collection);
                }
                $this->outputLine('Added asset %s to the collection %s', [$asset->getIdentifier(), $collection->getTitle()]);
            }
        }
    }
}

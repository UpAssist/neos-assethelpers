<?php

namespace UpAssist\Neos\AssetHelpers\Command;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Exception;
use Neos\Flow\Persistence\Exception\IllegalObjectTypeException;
use Neos\Flow\Persistence\Exception\InvalidQueryException;
use Neos\Flow\ResourceManagement\Collection;
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

    /**
     * @param string $collectionName
     * @param bool $confirm
     * @return void
     * @throws InvalidQueryException
     */
    public function removeCollectionAndAssetsCommand(string $collectionName, bool $confirm = false)
    {
        $assets = $this->assetRepository->findAll();
        /** @var Collection $collection */
        $collection = $this->assetCollectionRepository->findOneByTitle($collectionName);

        if (empty($collection)) {
            $this->outputLine('The collection with the name %s could not be found', [$collectionName]);
        }
        if (empty($assets)) {
            $this->outputLine('Something must be wrong or you have no assets uploaded yet: assets could not be found');
        }
        if ($assets && $collection) {
            $count = 0;
            /** @var Asset $asset */
            foreach ($assets as $asset) {
                if (in_array($collection, $asset->getAssetCollections()->toArray())) {
                    $count++;
                    if ($confirm) {
                        try {
                            $this->assetRepository->remove($asset);
                        } catch (Exception $exception) {
                            $this->outputLine('Asset %s is not removed because: %s', [$asset->getIdentifier(), $exception->getMessage()]);
                        }
                    }
                }
            }
            if (!$confirm) {
                $this->outputLine('There are %d assets found inside %s. Are you sure you want to delete them? Run again with --confirm true', [$count, $collection->getTitle()]);
            }
            if ($confirm) {
                $this->outputLine('There are %d assets removed inside %s.', [$count, $collection->getTitle()]);
                $this->outputLine('Please remove the collection %s from within Neos.', [$collection->getTitle()]);
            }
        }
    }
}

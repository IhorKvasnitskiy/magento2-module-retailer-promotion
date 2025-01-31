<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future.
 *
 * @category  Smile
 * @package   Smile\RetailerPromotion
 * @author    Fanny DECLERCK <fadec@smile.fr>
 * @copyright 2019 Smile
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Smile\RetailerPromotion\Model;

use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use Magento\Catalog\Model\ImageUploader;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\MediaStorage\Helper\File\Storage\Database;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Smile\RetailerPromotion\Api\Data\PromotionInterface;

/**
 * PromotionMediaUpload Model
 *
 * @category Smile
 * @package  Smile\RetailerPromotion
 * @author   Fanny DECLERCK <fadec@smile.fr>
 */
class PromotionMediaUpload
{
    /**
     * Image Uploader.
     *
     * @var ImageUploader
     */
    protected $imageUploader;

    /**
     * Directory List.
     *
     * @var DirectoryList
     */
    protected $directoryList;

    /**
     * Core file storage database
     *
     * @var Database
     */
    protected $coreFileStorageDatabase;

    /**
     * Media directory object (writable).
     *
     * @var WriteInterface
     */
    protected $mediaDirectory;

    /**
     * PromotionMediaUpload constructor.
     *
     * @param ImageUploader $imageUploader           Image uploader
     * @param DirectoryList $directoryList           Directory List.
     * @param Database      $coreFileStorageDatabase File Storage Database.
     * @param Filesystem    $filesystem              File System.
     *
     * @throws FileSystemException
     */
    public function __construct(
        ImageUploader $imageUploader,
        DirectoryList $directoryList,
        Database $coreFileStorageDatabase,
        Filesystem $filesystem
    ) {
        $this->imageUploader           = $imageUploader;
        $this->directoryList           = $directoryList;
        $this->coreFileStorageDatabase = $coreFileStorageDatabase;
        $this->mediaDirectory          = $filesystem->getDirectoryWrite(
            \Magento\Framework\App\Filesystem\DirectoryList::MEDIA
        );
    }

    /**
     * Remove media From Tmp Directory.
     *
     * @param PromotionInterface $promotion Promotion.
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function removeMediaFromTmp(PromotionInterface $promotion)
    {
        $media = str_replace($promotion->getRetailerId().'_', '', $promotion->getMediaPath());
        $baseTmpPath = $this->imageUploader->getBaseTmpPath();
        if (!empty($media) && $this->pathExist($baseTmpPath, $media)) {
            $this->moveFileFromTmp($media, $promotion);
        }
    }

    /**
     * Move media From Tmp Directory.
     *
     * @param string             $imageName Image name.
     * @param PromotionInterface $promotion Promotion.
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function moveFileFromTmp($imageName, $promotion)
    {
        $baseTmpPath = $this->imageUploader->getBaseTmpPath();
        $basePath = $this->imageUploader->getBasePath();

        $baseImagePath = $this->imageUploader->getFilePath(
            $basePath,
            $promotion->getRetailerId().'_'.$imageName
        );
        $baseTmpImagePath = $this->imageUploader->getFilePath($baseTmpPath, $imageName);

        try {
            $this->coreFileStorageDatabase->copyFile(
                $baseTmpImagePath,
                $baseImagePath
            );
            $this->mediaDirectory->renameFile(
                $baseTmpImagePath,
                $baseImagePath
            );
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Something went wrong while saving the file(s).')
            );
        }
    }

    /**
     * Remove File.
     *
     * @param PromotionInterface $promotion Promotion.
     *
     * @return void
     * @throws FileSystemException
     */
    public function removeMedia(PromotionInterface $promotion)
    {
        $media = $promotion->getRetailerId().'_'.$promotion->getMediaPath();
        $basePath = $this->imageUploader->getBasePath();
        if (!empty($media) && $this->pathExist($basePath, $media)) {
            $this->removeFile($media);
        }
    }

    /**
     * Check if media exist.
     *
     * @param string $basePath Base Path.
     * @param string $fileName File Name.
     *
     * @return bool
     * @throws FileSystemException
     */
    public function pathExist($basePath, $fileName)
    {
        return file_exists($this->getPath($basePath, $fileName));
    }

    /**
     * Get media Path.
     *
     * @param string $basePath Base Path.
     * @param string $fileName File Name.
     *
     * @return string
     * @throws FileSystemException
     */
    public function getPath($basePath, $fileName)
    {
        return $this->directoryList->getPath(DirectoryList::MEDIA) .
            '/'
            . $this->imageUploader->getFilePath($basePath, $fileName);
    }

    /**
     * Remove File.
     *
     * @param string $fileName File Name.
     *
     * @return void
     *
     * @throws FileSystemException
     */
    public function removeFile($fileName)
    {
        unlink($this->getPath($this->imageUploader->getBasePath(), $fileName));
    }
}

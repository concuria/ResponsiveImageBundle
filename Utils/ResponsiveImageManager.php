<?php

namespace ResponsiveImageBundle\Utils;


use ResponsiveImageBundle\Event\ImageEvent;
use ResponsiveImageBundle\Event\ImageEvents;

/**
 * Class ImageManager
 * @package ResponsiveImageBundle\Utils
 */
class ResponsiveImageManager
{
    /**
     * @var
     */
    private $config;

    /**
     * @var
     */
    private $imager;

    /**
     * @var
     */
    private $styleManager;

    /**
     * @var
     */
    private $system;

    /**
     * @var
     */
    private $dispatcher;

    /**
     * @var array
     */
    private $s3;

    /**
     * @var array
     */
    private $images = [];

    /**
     * @var bool
     */
    private $remoteSource = FALSE;

    /**
     * ImageManager constructor.
     * @param $imager
     * @param $config
     */
    public function __construct($imager, $styleManager, $config, $system, $dispatcher, $s3)
    {
        $this->imager = $imager;
        $this->styleManager = $styleManager;
        $this->config = $config;
        $this->system = $system;
        $this->dispatcher = $dispatcher;
        $this->s3 = $s3;
    }

    /**
     * Creates a single styled image for an image object.
     *
     * @param $imageObject
     * @param $styleName
     * @param bool $storePath
     * @param bool $tmp
     * @return mixed
     */
    public function createImageDerivative($imageObject, $styleName)
    {
        $system = $this->system;

        $filename = $imageObject->getPath();

        // Where's the original file? AWS or local?
        // If AWS fetch the file and store in temp directory if there is one, set $this->sourceFetched.
        // $filePath = $system->uploadedFilePath($imageObject->getPath());
        $filePath = $this->getSourceFile($imageObject);

        // @TODO: This is possibly the temp path.
        // $stylePath = $system->styleDirectoryPath($styleName);
        $stylePath = $system->getStorageDirectory('save', NULL, $styleName);




        $style = $this->styleManager->getStyle($styleName);

        $crop = empty($imageObject) ? null : $imageObject->getCropCoordinates();

        // @TODO: Destination should be switchable to temporary directory.
        // $image = $this->imager->createImage($filePath, $stylePath, $style, $crop);

        // Store this here for postprocessing.
        $styleTree = $system->getStyleTree($styleName);
        $this->images[$styleName] = [$stylePath .$filename, $styleTree . '/' . $filename];

        var_dump($this->images);

        // Despatch event to any listeners.
        // $event = new ImageEvent($imageObject, $style);
        // $this->dispatcher->dispatch(
        //     ImageEvents::IMAGE_GENERATED,
        //     $event
        // );

        // return $image;
    }

    /**
     * @param $image
     * @return string
     */
    public function getSourceFile($image) {
        $filename = $image->getPath();


        if (!empty($this->images[0])) {
            return $this->images[0][0];
        }
        else {
            // The original file is in difference places depending on the local file policy.
            $local_policy = $this->config['aws_s3']['local_file_policy'];
            switch ($local_policy) {
                case 'NONE':
                    // @TODO: We need to download the file.
                    // @TODO: Once its downloaded and ready the path is temporary.
                    $fetchFromS3 = TRUE;
                    $action = 'temporary';
                    break;

                default:
                    $action = 'save';
                    break;
            }

            $directory = $this->system->getStorageDirectory($action);
            $path = $directory . $filename;
            $tree = $this->system->getUploadsDir() . '/' . $filename;

            // If the policy was set to keep no files, the original should be downloaded from s3.
            if (!empty($fetchFromS3)) {
                $s3key = empty($this->config['aws_s3']['directory']) ? $tree :  $this->config['aws_s3']['directory'] . '/' . $tree;
                $this->system->directoryExists($directory , TRUE);
                $this->s3->fetchS3Object($path, $s3key);
            }


            $this->images[0] = [$path, $tree];
        }


        return $path;
    }

    /**
     * Creates all styled images for a given image object.
     *
     * @param ResponsiveImageInterface $image
     */
    public function createAllStyledImages(ResponsiveImageInterface $image)
    {
        // $this->imagePaths = [];
        $filename = $image->getPath();
        $styles = $this->styleManager->getAllStyles();
        if (!empty($filename)) {
            foreach ($styles as $stylename => $style) {
                $this->createImageDerivative($image, $stylename, TRUE);
            }
        }
    }

    /**
     * @param ResponsiveImageInterface $image
     */
    public function deleteAllStyledImages(ResponsiveImageInterface $image)
    {
        $filename = $image->getPath();
        if (!empty($filename)) {
            $this->styleManager->deleteImageStyledFiles($filename);
        }
    }

    /**
     * Transfer files to S3 bucket
     *
     * @param ResponsiveImageInterface $image
     */
    public function transferToS3(ResponsiveImageInterface $image)
    {
        $file = $image->getPath();
        $paths = $this->styleManager->createPathsArray($file);

        $this->s3->setPaths($paths);
        $this->s3->uploadToS3();
    }

    /**
     * Delete temporary files
     *
     * @param ResponsiveImageInterface $image
     */
    public function removeFiles(ResponsiveImageInterface $image) {
        $file = $image->getPath();
        $paths = $this->styleManager->createPathsArray($file);
        
        $this->s3->setPaths($paths);
        $this->s3->removeFromS3();
    }

    /**
     * Delete temporary files
     *
     * @param ResponsiveImageInterface $image
     */
    public function deleteTempFiles(ResponsiveImageInterface $image = null) {
        
    }
}
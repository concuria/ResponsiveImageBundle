<?php

namespace ResponsiveImageBundle\Utils;


/**
 * Class FileSystem
 *
 * @package ResponsiveImageBundle\Utils
 */
class FileSystem
{
    /**
     * @var $config
     */
    private $awsConfig;

    /**
     * @var string
     */
    private $rootDir;

    /**
     * @var string
     */
    private $stylesDir;

    /**
     * @var string
     */
    private $systemPath;

    /**
     * @var string
     */
    private $systemUploadPath;

    /**
     * @var string
     */
    private $systemStylesPath;

    /**
     * @var
     */
    private $tempDirectory = null;

    /**
     * @var
     */
    private $uploadsDir;

    /**
     * @var
     */
    private $webDirectory;

    /**
     * @var
     */
    private $webStylesDirectory;

    /**
     * FileSystem constructor.
     *
     * @param $rootDir
     * @param $imageConfigs
     */
    public function __construct($rootDir, $imageConfigs) {
        $uploadsDir = $imageConfigs['image_directory'];
        $stylesDir = $imageConfigs['image_styles_directory'];
        $symfonyDir = substr($rootDir, 0, -4);

        $this->setRootDir($symfonyDir);
        $this->setUploadsDir($uploadsDir);
        $this->setStylesDir($uploadsDir . '/' . $stylesDir);
        $this->setSystemPath($symfonyDir . '/web');
        $this->setSystemUploadPath($this->systemPath . '/' . $this->uploadsDir);
        $this->setSystemStylesPath($this->systemUploadPath . '/' . $stylesDir);

        // Set the temp directory if aws is enabled.
        if (!empty($imageConfigs['aws_s3'])) {
            if (!empty($imageConfigs['aws_s3']['enabled'])) {
                dump($imageConfigs);
                $this->awsConfig = $imageConfigs['aws_s3'];
                if (!empty($this->awsConfig['temp_directory'])) {
                    $this->tempDirectory = $symfonyDir . '/' . $this->awsConfig['temp_directory'];
                }
            }
        }
    }

    /**
     * @return string
     */
    public function getRootDir()
    {
        return $this->rootDir;
    }

    /**
     * @param string $rootDir
     */
    public function setRootDir($rootDir)
    {
        $this->rootDir = $rootDir;
    }

    /**
     * @return mixed
     */
    public function getWebStylesDirectory()
    {
        return $this->webStylesDirectory;
    }

    /**
     * @param mixed $webStylesDirectory
     */
    public function setWebStylesDirectory($webStylesDirectory)
    {
        $this->webStylesDirectory = $webStylesDirectory;
    }

    /**
     * @return mixed
     */
    public function getWebDirectory()
    {
        return $this->webDirectory;
    }

    /**
     * @param mixed $webDirectory
     */
    public function setWebDirectory($webDirectory)
    {
        $this->webDirectory = $webDirectory;
    }

    /**
     * @return string
     */
    public function getSystemStylesPath()
    {
        return $this->systemStylesPath;
    }

    /**
     * @param string $systemStylesPath
     */
    public function setSystemStylesPath($systemStylesPath)
    {
        $this->systemStylesPath = $systemStylesPath;
    }

    /**
     * @return string
     */
    public function getSystemUploadPath()
    {
        return $this->systemUploadPath;
    }

    /**
     * @param string $systemUploadPath
     */
    public function setSystemUploadPath($systemUploadPath)
    {
        $this->systemUploadPath = $systemUploadPath;
    }

    /**
     * @return string
     */
    public function getSystemPath()
    {
        return $this->systemPath;
    }

    /**
     * @param string $systemPath
     */
    public function setSystemPath($systemPath)
    {
        $this->systemPath = $systemPath;
    }

    /**
     * @return string
     */
    public function getStylesDir()
    {
        return $this->stylesDir;
    }

    /**
     * @param string $stylesDir
     */
    public function setStylesDir($stylesDir)
    {
        $this->stylesDir = $stylesDir;
    }

    /**
     * @return mixed
     */
    public function getUploadsDir()
    {
        return $this->uploadsDir;
    }

    /**
     * @param mixed $uploadsDir
     */
    public function setUploadsDir($uploadsDir)
    {
        $this->uploadsDir = $uploadsDir;
    }

    /**
     * @param $stylename
     * @return string
     */
    public function getStyleTree($stylename) {
        return $this->stylesDir . '/' . $stylename;
    }

    /**
     * @return string
     */
    public function getSystemUploadDirectory() {
        return $this->systemUploadPath;
    }

    /**
     * Check if a directory exists in the system and optionally create it if it doesn't
     *
     * @param $directory
     * @param bool $create
     * @return bool
     */
    public function directoryExists($directory, $create = FALSE) {
        if (file_exists($directory)) {
            return TRUE;
        }
        elseif (!file_exists($directory) && $create) {
            return mkdir($directory, 0775, TRUE);
        }
        else {
            return FALSE;
        }
    }

    /**
     * @param $fileName
     * @return mixed
     */
    public function fileExists($fileName) {
        $originalPath = $this->getStorageDirectory('original', $fileName);
        // $originalPath = $this->uploadedFilePath($fileName);
        return file_exists($originalPath);
    }

    /**
     * Deletes a directory and its contents or a file.
     *
     * @param $target
     * @return bool
     */
    public function deleteDirectory($target) {
        if(is_dir($target)){
            $files = glob($target . '/*');
            foreach($files as $file)
            {
                $this->deleteFile($file);
            }
            rmdir($target);
        }
        elseif (is_file($target)) {
            $this->deleteFile($target);
        }
    }

    /**
     *
     */
    public function clearTemporaryFiles() {
        $temp = $this->getTempDirectory();
        $uploadsFolder = $this->getUploadsDir();
        $this->deleteDirectory($temp . $uploadsFolder);
    }

    /**
     * @param $path
     * @return bool
     */
    public function deleteFile($path) {
        // If path exists delete the file.
        if ($this->directoryExists($path)) {
            unlink($path);
        }
    }

    /**
     * @param $filename
     * @return string
     */
    public function uploadedFilePath($filename) {
        return $this->systemUploadPath . '/' . $filename;
    }

    /**
     * @param $stylename
     * @return string
     */
    public function styleDirectoryPath($stylename) {
        return $this->systemStylesPath . '/' . $stylename;
    }

    /**
     * @param $filename
     * @return string
     */
    public function uploadedFileWebPath($filename) {
        return $this->uploadsDir . '/' . $filename;
    }

    /**
     * @param $stylename
     * @param $filename
     * @return string
     */
    public function styleFilePath($stylename, $filename) {
        return $this->styleDirectoryPath($stylename) . '/' . $filename;
    }

    /**
     * @param $path
     * @return string
     */
    public function getFilenameFromPath($path) {
        return basename($path);
    }

    /**
     * @param $stylename
     * @return string
     */
    public function styleWebPath($stylename) {
        $stylesDirectory = $this->stylesDir;
        $path = $stylesDirectory . '/' . $stylename;

        return $path;
    }

    /**
     * Returns the web accessible styled file path.
     *
     * @param $stylename
     * @return string
     */
    public function styledFileWebPath($stylename, $filename) {
        $stylesDirectory = $this->stylesDir;
        $path = $stylesDirectory . '/' . $stylename . '/' . $filename;

        return $path;
    }

    /**
     * Return the temporary directory if it has been set.
     *
     * @return bool|string
     */
    public function getTempDirectory () {
        if ($this->tempDirectory != NULL) {
            $this->directoryExists($this->tempDirectory, TRUE);
            return $this->tempDirectory;
        }
        else {
            return sys_get_temp_dir();
        }
    }

    /**
     * Returns the appropriate local storage directory based on the current operation and config parameters.
     *
     * @param $operation
     * @return string
     */
    public function getStorageDirectory($operation = 'original', $filename = NULL, $stylename = NULL) {
        // If AWS is not enabled the directory is the image_directory directory
        if (!empty($this->awsConfig) && !empty($this->awsConfig['enabled'])) {

            // $local_file_policy = $this->awsConfig['local_file_policy'];
            $remote_file_policy = $this->awsConfig['remote_file_policy'];
            switch ($operation) {
                case 'original':
                    if ($remote_file_policy == 'ALL') {
                        $directory = $this->getTempDirectory();
                    }
                    else {
                        $directory = $this->getSystemUploadDirectory();
                    }
                    break;
                case 'styled':
                    $directory = $this->getTempDirectory();
                    break;

                case 'temporary':
                    // Use the temporary directory.
                    $directory = $this->getTempDirectory();
                    break;

                default:
                    // Use the web directory by default.
                    $directory = $this->getSystemUploadDirectory();
                    break;
            }
        }
        else {
            if (empty($stylename)) {
                $directory = $this->getSystemUploadDirectory();
            }
            else {
                $directory = $this->getSystemPath() . '/';
            }
        }

        if ($stylename !== NULL) {
            $styleTree = $this->getStyleTree($stylename);
            $directory .=  $styleTree . '/';
        }

        // Check the trailing slash.
        $directory = $this->trailingSlash($directory, TRUE);

        // Add the filename on the end.
        if (!empty($filename)) {
            $directory .=  $filename;
        }

        return $directory;
    }

    /**
     * Ensures that a string has a trailing slash or not.
     *
     * @param $path
     * @param bool $slash
     * @return string
     */
    public function trailingSlash($path, $slash = TRUE) {
        $path = rtrim($path, '/');
        if ($slash) {
            $path .= '/';
        }

        return $path;
    }
}
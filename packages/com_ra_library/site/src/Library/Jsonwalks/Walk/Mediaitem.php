<?php
namespace Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Walk;
/**
 * Description of media
 *
 * @author Chris Vaughan
 */
class Mediaitem implements \JsonSerializable {

    public $alt = "";       // alternate text if image cannot be displayed or for accessiblity
    public $thumb = "";    // thumbnail size image
    public $medium = "";   // medium size for normal full display of image

    public function __construct(string $alt, string $thumb, string $medium) {
        $this->alt = $alt;
        $this->thumb = $thumb;
        $this->medium = $medium;
    }

    public function jsonSerialize(): mixed {
        return ['alt' => $this->alt,
            'thumb' => $this->thumb,
            'medium' => $this->medium
        ];
    }

}

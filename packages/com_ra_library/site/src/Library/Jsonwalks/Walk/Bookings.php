<?php
namespace Ramblers\Component\Ra_library\Site\Library\Jsonwalks\Walk;
/**
 * Description of contacts
 *
 * @author chris
 */
class Bookings implements \JsonSerializable {

    private $enabled = false;

    public function __construct($enabled) {
        $this->enabled = $enabled;
    }

    public function enabled() {
        return $this->enabled;
    }

    #[\Override]
    public function jsonSerialize(): mixed {
        return [
            'enabled' => $this->enabled
        ];
    }
}

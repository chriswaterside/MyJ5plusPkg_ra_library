<?php

namespace Ramblers\Component\RaLibrary\Tests\Unit\Library\Geodesy;

use PHPUnit\Framework\TestCase;
use Ramblers\Component\RaLibrary\Site\Library\Geodesy\OsCoordinateTransformer;

/**
 * Regression suite for OsCoordinateTransformer, encoding Ordnance Survey's
 * own OSTN15_OSGM15_Lite worked example test points.
 *
 * Source: OSTN15_OSGM15_Lite Developer Pack, test input/result files
 *   - OSTN15_OSGM15_Lite_TestInput_ETRSplHtoOSGBENh.txt (+ _RESULT.txt)
 *   - OSTN15_OSGM15_Lite_TestInput_OSGBENhtoETRSplH.txt (+ _RESULT.txt)
 *
 * These test points were used to manually verify the transformation
 * pipeline against OS's reference output before this class was written
 * up -- both directions matched to sub-millimetre precision. This suite
 * exists to catch any future regression in that accuracy.
 */
final class OsCoordinateTransformerTest extends TestCase
{
    private OsCoordinateTransformer $transformer;

    protected function setUp(): void
    {
        $gridPath = __DIR__ . '/../../../fixtures/OSTN15_OSGM15_Lite_DataFile.txt';
        $this->transformer = new OsCoordinateTransformer($gridPath);
    }

    /**
     * @dataProvider wgs84ToOsgb36Provider
     */
    public function testWgs84ToOsgb36ConvertsAccurately(
        float $lat,
        float $lon,
        float $expectedEasting,
        float $expectedNorthing
    ): void {
        [$easting, $northing] = $this->transformer->wgs84ToOsgb36($lat, $lon);

        // Allow up to 1mm tolerance -- observed floating point noise in
        // testing was on the order of a few thousandths of a millimetre.
        $this->assertEqualsWithDelta($expectedEasting, $easting, 0.001, 'Easting mismatch');
        $this->assertEqualsWithDelta($expectedNorthing, $northing, 0.001, 'Northing mismatch');
    }

    /**
     * @dataProvider osgb36ToWgs84Provider
     */
    public function testOsgb36ToWgs84ConvertsAccurately(
        float $easting,
        float $northing,
        float $expectedLat,
        float $expectedLon
    ): void {
        [$lat, $lon] = $this->transformer->osgb36ToWgs84($easting, $northing);

        // Tolerance expressed in degrees; ~0.00000001 degrees is sub-millimetre at UK latitudes.
        $this->assertEqualsWithDelta($expectedLat, $lat, 0.00000001, 'Latitude mismatch');
        $this->assertEqualsWithDelta($expectedLon, $lon, 0.00000001, 'Longitude mismatch');
    }

    /**
     * From OSTN15_OSGM15_Lite_TestInput_ETRSplHtoOSGBENh.txt and its _RESULT.txt.
     *
     * @return array<string, array{0: float, 1: float, 2: float, 3: float}>
     */
    public static function wgs84ToOsgb36Provider(): array
    {
        return [
            'point001' => [52.139417789376, -4.571313103567, 224134.49586, 252130.80956],
            'point002' => [52.789212654533, -4.741339469398, 215244.13150, 324815.20067],
            'point003' => [52.153163884799, 1.602825606285, 646552.92935, 256661.75921],
            'point004' => [51.677194028222, -0.559355149961, 499706.56215, 198584.67460],
            'point005' => [51.689311536318, -5.079176757909, 187269.16987, 203439.31921],
        ];
    }

    /**
     * From OSTN15_OSGM15_Lite_TestInput_OSGBENhtoETRSplH_RESULT.txt (RESULT rows).
     *
     * @return array<string, array{0: float, 1: float, 2: float, 3: float}>
     */
    public static function osgb36ToWgs84Provider(): array
    {
        return [
            'point001' => [224134.49586, 252130.80956, 52.1394177933, -4.57131310122],
            'point002' => [215244.13150, 324815.20067, 52.7892126575, -4.74133946144],
            'point003' => [646552.92935, 256661.75921, 52.1531638825, 1.6028255984],
            'point004' => [499706.56215, 198584.67460, 51.6771940318, -0.559355151983],
            'point005' => [187269.16987, 203439.31921, 51.6893115343, -5.07917675459],
            'point006' => [245941.58494, 130923.11651, 51.0569236671, -4.19965664214],
        ];
    }
}

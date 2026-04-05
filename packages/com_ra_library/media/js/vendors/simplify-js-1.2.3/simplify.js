/*
 (c) 2013, Vladimir Agafonkin
 Simplify.js, a high-performance JS polyline simplification library
 mourner.github.io/simplify-js
 */
var simplify2D;
(function () {
    "use strict";
// to suit your point format, run search/replace for '.x', '.y' and '.z';
// (configurability would draw significant performance overhead)
    function getx(p) {
        return p.lat;
    }
    function gety(p) {
        return p.lng;
    }
    function getz(p) {
        switch (simplify2D) {
            case "3D":
                return p.alt / 111000;
                break;
            case "2D":
                return 0;
                break;
            default:
                return p.alt / 100;
        }
    }

// square distance between 2 points
    function getSquareDistance(p1, p2) {

        var dx = getx(p1) - getx(p2),
                dy = gety(p1) - gety(p2),
                dz = getz(p1) - getz(p2);
        return dx * dx + dy * dy + dz * dz;
    }

// square distance from a point to a segment
    function getSquareSegmentDistance(p, p1, p2) {

        var x = getx(p1),
                y = gety(p1),
                z = getz(p1),
                dx = getx(p2) - x,
                dy = gety(p2) - y,
                dz = getz(p2) - z;
        if (dx !== 0 || dy !== 0 || dz !== 0) {

            var t = ((getx(p) - x) * dx + (gety(p) - y) * dy + (getz(p) - z) * dz) /
                    (dx * dx + dy * dy + dz * dz);
            if (t > 1) {
                x = getx(p2);
                y = gety(p2);
                z = getz(p2);
            } else if (t > 0) {
                x += dx * t;
                y += dy * t;
                z += dz * t;
            }
        }

        dx = getx(p) - x;
        dy = gety(p) - y;
        dz = getz(p) - z;
        return dx * dx + dy * dy + dz * dz;
    }
// the rest of the code doesn't care for the point format

// basic distance-based simplification
    function simplifyRadialDistance(points, sqTolerance) {

        var prevPoint = points[0],
                newPoints = [prevPoint],
                point;
        for (var i = 1, len = points.length; i < len; i++) {
            point = points[i];
            if (getSquareDistance(point, prevPoint) > sqTolerance) {
                newPoints.push(point);
                prevPoint = point;
            }
        }

        if (prevPoint !== point) {
            newPoints.push(point);
        }

        return newPoints;
    }

// simplification using optimized Douglas-Peucker algorithm with recursion elimination
    function simplifyDouglasPeucker(points, sqTolerance) {

        var len = points.length,
                MarkerArray = typeof Uint8Array !== 'undefined' ? Uint8Array : Array,
                markers = new MarkerArray(len),
                first = 0,
                last = len - 1,
                stack = [],
                newPoints = [],
                i, maxSqDist, sqDist, index;
        markers[first] = markers[last] = 1;
        while (last) {

            maxSqDist = 0;
            for (i = first + 1; i < last; i++) {
                sqDist = getSquareSegmentDistance(points[i], points[first], points[last]);
                if (sqDist > maxSqDist) {
                    index = i;
                    maxSqDist = sqDist;
                }
            }

            if (maxSqDist > sqTolerance) {
                markers[index] = 1;
                stack.push(first, index, index, last);
            }

            last = stack.pop();
            first = stack.pop();
        }

        for (i = 0; i < len; i++) {
            if (markers[i]) {
                newPoints.push(points[i]);
            }
        }

        return newPoints;
    }

// both algorithms combined for awesome performance
    function simplify(points, tolerance, highestQuality) {

        var sqTolerance = tolerance !== undefined ? tolerance * tolerance : 1;
        points = highestQuality ? points : simplifyRadialDistance(points, sqTolerance);
        points = simplifyDouglasPeucker(points, sqTolerance);
        return points;
    }

// export as a Node module, an AMD module or a global browser variable
    if (typeof module !== 'undefined') {
        module.exports = simplify;
    } else if (typeof define === 'function' && define.amd) {
        define(function () {
            return simplify;
        });
    } else {
        window.simplify = simplify;
    }

})();

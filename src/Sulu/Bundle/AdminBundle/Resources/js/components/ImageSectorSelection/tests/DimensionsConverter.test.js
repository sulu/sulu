/* eslint-disable flowtype/require-valid-file-annotation */
import DimensionsConverter from '../DimensionsConverter';

test('The converter should calculate the correct real horizontal value', () => {
    let converter = new DimensionsConverter(1920, 1080, 640, 360);
    expect(converter.computedHorizontalToReal(30)).toBe(10);
    expect(converter.computedHorizontalToReal(180)).toBe(60);

    converter = new DimensionsConverter(1920, 1080, 960, 540);
    expect(converter.computedHorizontalToReal(30)).toBe(15);
    expect(converter.computedHorizontalToReal(180)).toBe(90);
});

test('The converter should calculate the correct real vertical value', () => {
    let converter = new DimensionsConverter(1920, 1080, 640, 360);
    expect(converter.computedVerticalToReal(30)).toBe(10);
    expect(converter.computedVerticalToReal(180)).toBe(60);

    converter = new DimensionsConverter(1920, 1080, 960, 540);
    expect(converter.computedVerticalToReal(30)).toBe(15);
    expect(converter.computedVerticalToReal(180)).toBe(90);
});

test('The converter should calculate the correct real data', () => {
    let converter = new DimensionsConverter(1920, 1080, 640, 360);
    expect(converter.computedDataToReal({
        width: 900,
        height: 600,
        left: 15,
        top: 30,
    })).toEqual({
        width: 300,
        height: 200,
        left: 5,
        top: 10,
    });

    converter = new DimensionsConverter(1920, 1080, 960, 540);
    expect(converter.computedDataToReal({
        width: 900,
        height: 600,
        left: 16,
        top: 30,
    })).toEqual({
        width: 450,
        height: 300,
        left: 8,
        top: 15,
    });
});

test('The converter should calculate the correct computed data', () => {
    let converter = new DimensionsConverter(1920, 1080, 640, 360);
    expect(converter.realDataToComputed({
        width: 300,
        height: 200,
        left: 5,
        top: 10,
    })).toEqual({
        width: 900,
        height: 600,
        left: 15,
        top: 30,
    });

    converter = new DimensionsConverter(1920, 1080, 960, 540);
    expect(converter.realDataToComputed({
        width: 450,
        height: 300,
        left: 8,
        top: 15,
    })).toEqual({
        width: 900,
        height: 600,
        left: 16,
        top: 30,
    });
});

test('The converter should be the identity if computed an real values equal', () => {
    let converter = new DimensionsConverter(2, 3, 2, 3);
    expect(converter.computedHorizontalToReal(1)).toBe(1);
    expect(converter.computedHorizontalToReal(2)).toBe(2);
    expect(converter.computedVerticalToReal(3)).toBe(3);
    expect(converter.computedDataToReal({
        width: 3,
        height: 4,
        left: 5,
        top: 6,
    })).toEqual({
        width: 3,
        height: 4,
        left: 5,
        top: 6,
    });
    expect(converter.realDataToComputed({
        width: 7,
        height: 8,
        left: 9,
        top: 10,
    })).toEqual({
        width: 7,
        height: 8,
        left: 9,
        top: 10,
    });
});

/* eslint-disable flowtype/require-valid-file-annotation */
import RatioNormalizer from '../../normalizers/RatioNormalizer';

test('The RatioNormalizer should only make the values smaller, never bigger', () => {
    const ratio = new RatioNormalizer(1000, 500, 1, 2);

    let selection = ratio.normalize({width: 100, height: 50, left: 0, top: 0});

    expect(selection.width).toBeLessThanOrEqual(100);
    expect(selection.height).toBeLessThanOrEqual(50);

    selection = ratio.normalize({width: 50, height: 200, left: 0, top: 0});
    expect(selection.width).toBeLessThanOrEqual(100);
    expect(selection.height).toBeLessThanOrEqual(200);
});

test('The RatioNormalizer should keep width and height in the same ratio', () => {
    const ratio = new RatioNormalizer(1000, 500, 1, 2);

    let selection = ratio.normalize({width: 100, height: 50, left: 0, top: 0});
    expect(selection).toEqual({width: 25, height: 50, left: 0, top: 0});

    selection = ratio.normalize({width: 50, height: 50, left: 0, top: 0});
    expect(selection).toEqual({width: 25, height: 50, left: 0, top: 0});
});

test('The RatioNormalizer should not change an already valid selection', () => {
    const ratio = new RatioNormalizer(1000, 500, 3, 2);

    let selection = ratio.normalize({width: 300, height: 200, left: 0, top: 0});
    expect(selection).toEqual({width: 300, height: 200, left: 0, top: 0});

    selection = ratio.normalize({width: 12, height: 8, left: 0, top: 0});
    expect(selection).toEqual({width: 12, height: 8, left: 0, top: 0});
});

/* eslint-disable flowtype/require-valid-file-annotation */
import SizeNormalizer from '../../normalizers/SizeNormalizer';

test('The SizeNormalizer should correctly constrain the selection downwards', () => {
    let size = new SizeNormalizer(1000, 500);
    let selection = size.normalize({width: -10, height: -20, left: 0, top: 0});
    expect(selection).toEqual({width: 0, height: 0, left: 0, top: 0});

    size = new SizeNormalizer(1000, 500, 200, 300);
    selection = size.normalize({width: 100, height: 50, left: 0, top: 0});
    expect(selection).toEqual({width: 200, height: 300, left: 0, top: 0});
});

test('The SizeNormalizer should correctly constrain the selection upwards', () => {
    const size = new SizeNormalizer(1000, 500);
    const selection = size.normalize({width: 1500, height: 1000, left: 0, top: 0});
    expect(selection).toEqual({width: 1000, height: 500, left: 0, top: 0});
});

test('The SizeNormalizer should not alter correct selections', () => {
    const size = new SizeNormalizer(1000, 500);
    const selection = size.normalize({width: 800, height: 400, left: 0, top: 0});
    expect(selection).toEqual({width: 800, height: 400, left: 0, top: 0});
});

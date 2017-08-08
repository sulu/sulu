/* eslint-disable flowtype/require-valid-file-annotation */
import PositionNormalizer from '../../normalizers/PositionNormalizer';

test('The PositionNormalizer should correctly constrain the selection downwards', () => {
    const position = new PositionNormalizer(200, 300);
    const selection = position.normalize({width: 100, height: 50, left: -100, top: -50});
    expect(selection).toEqual({width: 100, height: 50, left: 0, top: 0});
});

test('The PositionNormalizer should correctly constrain the selection upwards', () => {
    const position = new PositionNormalizer(200, 300);
    const selection = position.normalize({width: 100, height: 50, left: 150, top: 300});
    expect(selection).toEqual({width: 100, height: 50, left: 100, top: 250});
});

test('The PositionNormalizer should not alter already normalized selections', () => {
    const position = new PositionNormalizer(200, 300);
    const selection = position.normalize({width: 100, height: 50, left: 20, top: 30});
    expect(selection).toEqual({width: 100, height: 50, left: 20, top: 30});
});

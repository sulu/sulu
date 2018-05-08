/* eslint-disable flowtype/require-valid-file-annotation */
import RoundingNormalizer from '../../normalizers/RoundingNormalizer';

test('The RoundingNormalizer should round correctly', () => {
    const size = new RoundingNormalizer();
    const selection = size.normalize({width: 1.33, height: 2.55, left: 6, top: 7.1234});
    expect(selection).toEqual({width: 1, height: 3, left: 6, top: 7});
});

test('The RoundingNormalizer should not alter already rounded selections', () => {
    const size = new RoundingNormalizer();
    const selection = size.normalize({width: 1, height: 2, left: 6, top: 7});
    expect(selection).toEqual({width: 1, height: 2, left: 6, top: 7});
});

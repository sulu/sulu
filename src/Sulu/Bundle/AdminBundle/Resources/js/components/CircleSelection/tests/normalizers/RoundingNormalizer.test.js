// @flow
import RoundingNormalizer from '../../normalizers/RoundingNormalizer';

test('The RoundingNormalizer should round correctly', () => {
    const size = new RoundingNormalizer();
    const selection = size.normalize({radius: 1.33, left: 5.879, top: 7.1234});
    expect(selection).toEqual({radius: 1, left: 6, top: 7});
});

test('The RoundingNormalizer should not alter already rounded selections', () => {
    const size = new RoundingNormalizer();
    const selection = size.normalize({radius: 1, left: 6, top: 7});
    expect(selection).toEqual({radius: 1, left: 6, top: 7});
});

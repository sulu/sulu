// @flow
import bundlesConditionDataProvider from '../../conditionDataProviders/bundlesConditionDataProvider';

jest.mock('../../../../services/initializer', () => ({
    bundles: ['sulu_admin', 'sulu_audience_targeting'],
}));

test('Return all bundles from initializer', () => {
    expect(bundlesConditionDataProvider()).toEqual({__bundles: ['sulu_admin', 'sulu_audience_targeting']});
});

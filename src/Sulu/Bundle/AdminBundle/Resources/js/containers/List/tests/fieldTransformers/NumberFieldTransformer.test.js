// @flow
import log from 'loglevel';
import NumberFieldTransformer from '../../fieldTransformers/NumberFieldTransformer';

const numberFieldTransformer = new NumberFieldTransformer();

jest.mock('loglevel', () => ({
    error: jest.fn(),
}));

const mockUserStoreUser = jest.fn().mockReturnValue({
    locale: jest.fn(),
});

jest.mock('../../../../stores/userStore', () => {
    return new class {
        get user() {
            return mockUserStoreUser();
        }
    };
});

test('Test undefined', () => {
    expect(numberFieldTransformer.transform(undefined)).toBe(null);
    expect(numberFieldTransformer.transform(null)).toBe(null);
});

test('Test invalid format', () => {
    expect(numberFieldTransformer.transform('xxx')).toBe(null);
    expect(log.error).toBeCalledWith('Invalid number given: "xxx"');
});

test('Test valid example', () => {
    expect(numberFieldTransformer.transform(20.3)).toBe('20.3');
});

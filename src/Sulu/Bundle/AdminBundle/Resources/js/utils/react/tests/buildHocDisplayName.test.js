/* eslint-disable flowtype/require-valid-file-annotation */
import buildHocDisplayName from '../buildHocDisplayName';

test('Build HOC display name with displayName property if it exists', () => {
    const Component = {
        displayName: 'componentDisplayName',
        name: 'componentName',
    };

    expect(buildHocDisplayName('withSomeHoc', Component)).toBe('withSomeHoc(componentDisplayName)');
});

test('Build HOC display name with name property if displayName property does not exist', () => {
    const Component = {
        name: 'componentName',
    };

    expect(buildHocDisplayName('withSomeHoc', Component)).toBe('withSomeHoc(componentName)');
});

test('Build HOC display name with empty string if neither displayName nor name propert exists', () => {
    const Component = {};
    expect(buildHocDisplayName('withSomeHoc', Component)).toBe('withSomeHoc()');
});

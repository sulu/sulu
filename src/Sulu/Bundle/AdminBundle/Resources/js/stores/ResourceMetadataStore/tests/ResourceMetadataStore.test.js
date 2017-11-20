/* eslint-disable flowtype/require-valid-file-annotation */
import resourceMetadataStore from '../ResourceMetadataStore';

test('Get base URL for given key', () => {
    expect(resourceMetadataStore.getBaseUrl('snippets')).toEqual('/admin/api/snippets');
});

test('Throw exception when getting base URL for not existing key', () => {
    expect(() => resourceMetadataStore.getBaseUrl('not-existing')).toThrow(/"not-existing"/);
});

test('Load configuration for given key', () => {
    expect(resourceMetadataStore.loadConfiguration('snippets')).toEqual({
        list: {
            id: {},
            title: {},
            template: {},
            changed: {},
            created: {},
        },
        form: {
            title: {
                label: 'Title',
                type: 'text_line',
            },
            slogan: {
                label: 'Slogan',
                type: 'text_line',
            },
        },
    });
});

test('Throw exception when loading configuration for not existing key', () => {
    expect(() => resourceMetadataStore.loadConfiguration('not-existing')).toThrow(/"not-existing"/);
});

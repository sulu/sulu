// @flow
import sidebarStore, {DEFAULT_SIZE} from '../../stores/sidebarStore';

beforeEach(() => {
    sidebarStore.clearConfig();
});

test('Set sidebar config and let mobx react', () => {
    const config = {
        view: 'preview',
        props: {
            id: 1,
        },
    };

    sidebarStore.setConfig(config);

    expect(sidebarStore.enabled).toEqual(true);
    expect(sidebarStore.view).toEqual(config.view);
    expect(sidebarStore.props).toEqual(config.props);
    expect(sidebarStore.size).toEqual('medium');
});

test('Default size of sidebar should be small', () => {
    sidebarStore.setConfig({
        view: 'preview',
    });

    expect(sidebarStore.view).toEqual('preview');
    expect(sidebarStore.size).toEqual(DEFAULT_SIZE);
});

test('Set sidebar size', () => {
    expect(sidebarStore.size).toEqual(null);
    sidebarStore.setSize('large');
    expect(sidebarStore.size).toEqual('large');
});

test('Clear sidebar config', () => {
    sidebarStore.setConfig({
        view: 'preview',
    });

    expect(sidebarStore.view).toEqual('preview');

    sidebarStore.clearConfig();
    expect(sidebarStore.view).toEqual(undefined);
});

test('Use default size if current size not supported', () => {
    sidebarStore.size = 'large';

    sidebarStore.setConfig({
        view: 'preview',
        sizes: ['small'],
        defaultSize: 'small',
    });

    expect(sidebarStore.size).toEqual('small');
});

test('Use default size if current size not set', () => {
    sidebarStore.clearConfig();

    sidebarStore.setConfig({
        view: 'preview',
        sizes: ['small'],
        defaultSize: 'small',
    });

    expect(sidebarStore.size).toEqual('small');
});

test('Throw error when size is not supported', () => {
    sidebarStore.setConfig({
        view: 'preview',
        sizes: ['small'],
        defaultSize: 'small',
    });

    expect(() => {
        sidebarStore.setSize('medium');
    }).toThrow(new Error('Size "medium" is not supported by view. Supported: ["small"]'));
});

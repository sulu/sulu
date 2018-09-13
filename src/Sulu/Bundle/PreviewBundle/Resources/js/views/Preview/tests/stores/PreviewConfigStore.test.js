// @flow
import previewConfigStore from '../../stores/PreviewConfigStore';

test('Set preview config', () => {
    const config = {
        routes: {
            start: '/start',
            render: '/render',
            update: '/update',
            stop: '/stop',
        },
        debounceDelay: 500,
        mode: 'off',
    };

    previewConfigStore.setConfig(config);

    expect(previewConfigStore.mode).toEqual('off');
    expect(previewConfigStore.debounceDelay).toEqual(500);
    expect(previewConfigStore.generateRoute('start', {foo: 'bar'})).toEqual('/start?foo=bar');
    expect(previewConfigStore.generateRoute('render', {foo: 'bar'})).toEqual('/render?foo=bar');
    expect(previewConfigStore.generateRoute('update', {foo: 'bar'})).toEqual('/update?foo=bar');
    expect(previewConfigStore.generateRoute('stop', {foo: 'bar'})).toEqual('/stop?foo=bar');
});

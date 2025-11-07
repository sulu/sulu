// @flow
import React from 'react';
import linkOverlayRegistry from '../../registries/linkOverlayRegistry';

beforeEach(() => {
    linkOverlayRegistry.clear();
});

test('Clear all information from linkOverlayRegistry', () => {
    const Component = () => (<div />);
    const DefaultComponent = () => (<div>Default</div>);

    linkOverlayRegistry.setDefaultOverlay(DefaultComponent);
    linkOverlayRegistry.add('test1', Component);
    expect(Object.keys(linkOverlayRegistry.overlays)).toHaveLength(2);

    linkOverlayRegistry.clear();
    expect(Object.keys(linkOverlayRegistry.overlays)).toHaveLength(0);
});

test('Set default overlay in linkOverlayRegistry', () => {
    const DefaultComponent = () => (<div>Default</div>);

    linkOverlayRegistry.setDefaultOverlay(DefaultComponent);
    expect(linkOverlayRegistry.overlays['default']).toBe(DefaultComponent);
});

test('Add overlay to linkOverlayRegistry', () => {
    const Component = () => (<div />);
    const AnotherComponent = () => (<div>Another</div>);

    linkOverlayRegistry.add('test1', Component);
    linkOverlayRegistry.add('test2', AnotherComponent);

    expect(linkOverlayRegistry.overlays['test1']).toBe(Component);
    expect(linkOverlayRegistry.overlays['test2']).toBe(AnotherComponent);
    expect(Object.keys(linkOverlayRegistry.overlays)).toHaveLength(2);
});

test('Get overlay returns specific overlay when type exists', () => {
    const Component = () => (<div />);
    const DefaultComponent = () => (<div>Default</div>);

    linkOverlayRegistry.setDefaultOverlay(DefaultComponent);
    linkOverlayRegistry.add('test1', Component);

    expect(linkOverlayRegistry.getOverlay('test1')).toBe(Component);
});

test('Get overlay returns default overlay when type does not exist', () => {
    const DefaultComponent = () => (<div>Default</div>);

    linkOverlayRegistry.setDefaultOverlay(DefaultComponent);

    expect(linkOverlayRegistry.getOverlay('nonexistent')).toBe(DefaultComponent);
});

test('Get overlay throws error when no overlays are registered', () => {
    expect(() => linkOverlayRegistry.getOverlay('test')).toThrow(
        /There is no overlay for an link type with the key "test" registered and no default overlay is set/
    );
});

test('Get overlay throws error with registered keys in message', () => {
    const Component = () => (<div />);
    linkOverlayRegistry.add('test1', Component);
    linkOverlayRegistry.add('test2', Component);

    expect(() => linkOverlayRegistry.getOverlay('nonexistent')).toThrow(/test1, test2/);
});

test('Add overlay can override existing overlay', () => {
    const Component1 = () => (<div>Component1</div>);
    const Component2 = () => (<div>Component2</div>);

    linkOverlayRegistry.add('test1', Component1);
    expect(linkOverlayRegistry.getOverlay('test1')).toBe(Component1);

    linkOverlayRegistry.add('test1', Component2);
    expect(linkOverlayRegistry.getOverlay('test1')).toBe(Component2);
});

test('Default overlay can be overridden', () => {
    const DefaultComponent1 = () => (<div>Default1</div>);
    const DefaultComponent2 = () => (<div>Default2</div>);

    linkOverlayRegistry.setDefaultOverlay(DefaultComponent1);
    expect(linkOverlayRegistry.getOverlay('nonexistent')).toBe(DefaultComponent1);

    linkOverlayRegistry.setDefaultOverlay(DefaultComponent2);
    expect(linkOverlayRegistry.getOverlay('nonexistent')).toBe(DefaultComponent2);
});

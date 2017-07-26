/* eslint-disable flowtype/require-valid-file-annotation */
import {mount, render} from 'enzyme';
import React from 'react';
import RectangleSelection from '../RectangleSelection';

class MockedRectangleSelection extends RectangleSelection {
    constructor(props) {
        super(props);
        this.spyed = false;
    }

    componentDidUpdate() {
        if (!this.spyed) {
            this.spyed = true;
            // Move the update spy to the end of the execution queue, in order to see rendering changes
            Promise.resolve().then(this.props.updateSpy);
        }
    }

    readContainerDimensions = () => {
        this.container = {clientWidth: 2000, clientHeight: 1000};
        this.containerHeight = this.container.clientHeight;
        this.containerWidth = this.container.clientWidth;
    };
}

test('The component should render with children', () => {
    const view = render(
        <RectangleSelection>
            <p>Lorem ipsum</p>
        </RectangleSelection>
    );

    expect(view).toMatchSnapshot();
});

test('The component should render with initial selection', (done) => {
    window.requestAnimationFrame = jest.fn((cb) => cb());

    let view;
    const spy = () => {
        expect(view.render()).toMatchSnapshot();
        done();
    };

    view = mount(
        <MockedRectangleSelection updateSpy={spy} initialSelection={{width: 1, height: 2, top: 3, left: 4}}>
            <p>Lorem ipsum</p>
        </MockedRectangleSelection>
    );
});

test('The component should maximize the selection when no initial values given', (done) => {
    window.requestAnimationFrame = jest.fn((cb) => cb());

    let view;
    const spy = () => {
        expect(view.render()).toMatchSnapshot();
        done();
    };

    view = mount(
        <MockedRectangleSelection updateSpy={spy}>
            <p>Lorem ipsum</p>
        </MockedRectangleSelection>
    );
});

test('The component should center and maximize the selection when a minHeight and minWidth is given', (done) => {
    window.requestAnimationFrame = jest.fn((cb) => cb());

    let view;
    const spy = () => {
        expect(view.render()).toMatchSnapshot();
        done();
    };

    view = mount(
        <MockedRectangleSelection updateSpy={spy} minHeight={200} minWidth={50}>
            <p>Lorem ipsum</p>
        </MockedRectangleSelection>
    );
    view = mount(
        <MockedRectangleSelection updateSpy={spy} minHeight={50} minWidth={200}>
            <p>Lorem ipsum</p>
        </MockedRectangleSelection>
    );
});

test('The component should publish the new selection when the rectangle changes', (done) => {
    window.requestAnimationFrame = jest.fn((cb) => cb());

    let view;
    let selection = {};
    const setSelection = (s) => selection = s;
    const spy = () => {
        view.instance().handleRectangleChange({width: -20, height: -30, left: 10, top: 20});
        expect(selection).toEqual({width: 1980, height: 970, top: 20, left: 10});
        done();
    };

    view = mount(
        <MockedRectangleSelection updateSpy={spy} onChange={setSelection}>
            <p>Lorem ipsum</p>
        </MockedRectangleSelection>
    );
});

test('The component should not allow the selection to move over the borders', (done) => {
    window.requestAnimationFrame = jest.fn((cb) => cb());

    let view;
    let selection = {};
    const setSelection = (s) => selection = s;
    const spy = () => {
        view.instance().handleRectangleChange({width: 0, height: 0, left: -10, top: -20});
        expect(selection).toEqual({width: 2000, height: 1000, top: 0, left: 0});
        done();
    };

    view = mount(
        <MockedRectangleSelection updateSpy={spy} onChange={setSelection}>
            <p>Lorem ipsum</p>
        </MockedRectangleSelection>
    );
});

test('The component should not allow the selection to be bigger than the container', (done) => {
    window.requestAnimationFrame = jest.fn((cb) => cb());

    let view;
    let selection = {};
    const setSelection = (s) => selection = s;
    const spy = () => {
        view.instance().handleRectangleChange({width: 10, height: 20, left: 0, top: 0});
        expect(selection).toEqual({width: 2000, height: 1000, top: 0, left: 0});
        done();
    };

    view = mount(
        <MockedRectangleSelection updateSpy={spy} onChange={setSelection}>
            <p>Lorem ipsum</p>
        </MockedRectangleSelection>
    );
});

test('The component should enforce a ratio on the selection if minWidth and minHeight are given', (done) => {
    window.requestAnimationFrame = jest.fn((cb) => cb());

    let view;
    let selection = {};
    const setSelection = (s) => selection = s;
    const spy = () => {
        view.instance().handleRectangleChange({width: -10, height: -250, left: 0, top: 0});
        expect(selection).toEqual({width: 375, height: 750, top: 0, left: 750});
        done();
    };

    view = mount(
        <MockedRectangleSelection minWidth={10} minHeight={20} updateSpy={spy} onChange={setSelection}>
            <p>Lorem ipsum</p>
        </MockedRectangleSelection>
    );
});

test('The component should should not round if told by the properties', (done) => {
    window.requestAnimationFrame = jest.fn((cb) => cb());

    let selection = {};
    const setSelection = (s) => selection = s;
    const spy = () => {
        expect(selection.width).toEqual(2000);
        expect(selection.height).toBeGreaterThan(666);
        expect(selection.height).toBeLessThan(667);
        expect(selection.left).toEqual(0);
        expect(selection.top).toBeGreaterThan(166);
        expect(selection.top).toBeLessThan(167);
        done();
    };

    mount(
        <MockedRectangleSelection round={false} minWidth={3} minHeight={1} updateSpy={spy} onChange={setSelection}>
            <p>Lorem ipsum</p>
        </MockedRectangleSelection>
    );
});

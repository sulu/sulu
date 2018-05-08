/* eslint-disable flowtype/require-valid-file-annotation */
import {mount, render} from 'enzyme';
import React from 'react';
import {RectangleSelection} from '../RectangleSelection';

jest.mock('../../withContainerSize/withContainerSize');
jest.mock('../../../services/DOM/afterElementsRendered');

class MockedRectangleSelection extends RectangleSelection {
    componentDidMount() {
        this.containerDidMount();
        Promise.resolve().then(this.props.mountSpy);
    }
}

test('The component should render with children', () => {
    const view = render(
        <MockedRectangleSelection containerWidth={2000} containerHeight={1000}>
            <p>Lorem ipsum</p>
        </MockedRectangleSelection>
    );

    expect(view).toMatchSnapshot();
});

test('The component should render with initial selection', (done) => {
    const spy = jest.fn(() => {
        expect(view.render()).toMatchSnapshot();
        done();
    });

    const view = mount(
        <MockedRectangleSelection
            containerWidth={2000}
            containerHeight={1000}
            mountSpy={spy}
            initialSelection={{width: 1, height: 2, top: 3, left: 4}}
        >
            <p>Lorem ipsum</p>
        </MockedRectangleSelection>
    );
});

test('The component should maximize the selection when no initial values given', (done) => {
    const spy = jest.fn(() => {
        expect(view.render()).toMatchSnapshot();
        done();
    });

    const view = mount(
        <MockedRectangleSelection mountSpy={spy} containerWidth={2000} containerHeight={1000}>
            <p>Lorem ipsum</p>
        </MockedRectangleSelection>
    );
});

test('The component should center and maximize the selection when a minHeight and minWidth is given', (done) => {
    let view;
    const spy = jest.fn(() => {
        expect(view.render()).toMatchSnapshot();
        done();
    });

    view = mount(
        <MockedRectangleSelection
            mountSpy={spy}
            minHeight={200}
            minWidth={50}
            containerWidth={2000}
            containerHeight={1000}
        >
            <p>Lorem ipsum</p>
        </MockedRectangleSelection>
    );
    view = mount(
        <MockedRectangleSelection
            mountSpy={spy}
            minHeight={50}
            minWidth={200}
            containerWidth={2000}
            containerHeight={1000}
        >
            <p>Lorem ipsum</p>
        </MockedRectangleSelection>
    );
});

test('The component should publish the new selection when the rectangle changes', (done) => {
    let selection = {};
    const setSelection = jest.fn((s) => selection = s);
    const spy = jest.fn(() => {
        view.instance().handleRectangleChange({width: -20, height: -30, left: 10, top: 20});
        expect(selection).toEqual({width: 1980, height: 970, top: 20, left: 10});
        done();
    });

    const view = mount(
        <MockedRectangleSelection
            mountSpy={spy}
            onChange={setSelection}
            containerWidth={2000}
            containerHeight={1000}
        >
            <p>Lorem ipsum</p>
        </MockedRectangleSelection>
    );
});

test('The component should not allow the selection to move over the borders', (done) => {
    let selection = {};
    const setSelection = jest.fn((s) => selection = s);
    const spy = jest.fn(() => {
        view.instance().handleRectangleChange({width: 0, height: 0, left: -10, top: -20});
        expect(selection).toEqual({width: 2000, height: 1000, top: 0, left: 0});
        done();
    });

    const view = mount(
        <MockedRectangleSelection mountSpy={spy} onChange={setSelection} containerWidth={2000} containerHeight={1000}>
            <p>Lorem ipsum</p>
        </MockedRectangleSelection>
    );
});

test('The component should not allow the selection to be bigger than the container', (done) => {
    let selection = {};
    const setSelection = jest.fn((s) => selection = s);
    const spy = jest.fn(() => {
        view.instance().handleRectangleChange({width: 10, height: 20, left: 0, top: 0});
        expect(selection).toEqual({width: 2000, height: 1000, top: 0, left: 0});
        done();
    });

    const view = mount(
        <MockedRectangleSelection
            mountSpy={spy}
            onChange={setSelection}
            containerWidth={2000}
            containerHeight={1000}
        >
            <p>Lorem ipsum</p>
        </MockedRectangleSelection>
    );
});

test('The component should enforce a ratio on the selection if minWidth and minHeight are given', (done) => {
    let selection = {};
    const setSelection = jest.fn((s) => selection = s);
    const spy = jest.fn(() => {
        view.instance().handleRectangleChange({width: -10, height: -250, left: 0, top: 0});
        expect(selection).toEqual({width: 375, height: 750, top: 0, left: 750});
        done();
    });

    const view = mount(
        <MockedRectangleSelection
            containerWidth={2000}
            containerHeight={1000}
            minWidth={10}
            minHeight={20}
            mountSpy={spy}
            onChange={setSelection}
        >
            <p>Lorem ipsum</p>
        </MockedRectangleSelection>
    );
});

test('The component should should not round if told by the properties', (done) => {
    let selection = {};
    const setSelection = jest.fn((s) => selection = s);
    const spy = jest.fn(() => {
        expect(selection.width).toEqual(2000);
        expect(selection.height).toBeGreaterThan(666);
        expect(selection.height).toBeLessThan(667);
        expect(selection.left).toEqual(0);
        expect(selection.top).toBeGreaterThan(166);
        expect(selection.top).toBeLessThan(167);
        done();
    });

    mount(
        <MockedRectangleSelection
            containerWidth={2000}
            containerHeight={1000}
            round={false}
            minWidth={3}
            minHeight={1}
            mountSpy={spy}
            onChange={setSelection}
        >
            <p>Lorem ipsum</p>
        </MockedRectangleSelection>
    );
});

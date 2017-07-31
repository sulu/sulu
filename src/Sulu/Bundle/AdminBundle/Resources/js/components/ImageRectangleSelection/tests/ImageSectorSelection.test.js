/* eslint-disable flowtype/require-valid-file-annotation */
/*import {mount, render} from 'enzyme';
import ImageRectangleSelection from '../ImageRectangleSelection';
import React from 'react';
import {action} from 'mobx';
import {observer} from 'mobx-react';

@observer
class MockedImageSelection extends ImageRectangleSelection {
    constructor(props) {
        super(props);
        this.spyed = false;
    }

    componentDidUpdate() {
        if (!this.spyed && this.props.updateSpy) {
            this.spyed = true;
            // Move the update spy to the end of the execution queue, in order to see rendering changes
            Promise.resolve().then(this.props.updateSpy);
        }
        super.componentDidUpdate();
    }

    componentWillMount() {
        this.image = {naturalWidth: 1920, naturalHeight: 1080};
        this.imageLoaded = true;
    }

    readContainerDimensions = action(() => {
        this.containerWidth = 640;
        this.containerHeight = 360;
    });
}

jest.mock('../../RectangleSelection', () => {
    const RectangleSelection = require.requireActual('../../RectangleSelection').default;
    const {observer} = require.requireActual('mobx-react');
    const {action} = require.requireActual('mobx');

    @observer
    class MockedRectangleSelection extends RectangleSelection {
        readContainerDimensions = action(() => {
            this.container = {clientWidth: 640, clientHeight: 360};
            this.containerHeight = this.container.clientHeight;
            this.containerWidth = this.container.clientWidth;
        });
    }

    return MockedRectangleSelection;
});

test('The component should render with image source', () => {
    const view = render(<ImageRectangleSelection src="//:0" />);
    expect(view).toMatchSnapshot();
});

test('The component should calculate the selection with respect to the image', (done) => {
    window.requestAnimationFrame = jest.fn((cb) => cb());

    const onChangeSpy = (data) => {
        expect(data).toEqual({width: 1920, height: 1080, top: 0, left: 0});
        done();
    };

    mount(
        <MockedImageSelection
            onChange={onChangeSpy}
            src="//:0" />
    );
});

test('The component should render with initial selection', (done) => {
    window.requestAnimationFrame = jest.fn((cb) => cb());

    const spy = () => {
        expect(view.render()).toMatchSnapshot();
        done();
    };
    const onChangeSpy = (data) => {
        expect(data).toEqual({ width: 1500, height: 800, top: 200, left: 300 });
    };

    const view = mount(
        <MockedImageSelection
            onChange={onChangeSpy}
            updateSpy={spy}
            src="//:0"
            initialSelection={{width: 1500, height: 800, top: 200, left: 300}} />
    );
});

test('The component should render with minWidth and minHeight', (done) => {
    window.requestAnimationFrame = jest.fn((cb) => cb());

    const spy = () => {
        const rectangle = view.find('MockedRectangleSelection');
        expect(rectangle.length).toBe(1);
        expect(rectangle.props().minWidth).toBe(200);
        expect(rectangle.props().minHeight).toBe(100);
        done();
    };

    const view = mount(
        <MockedImageSelection
            updateSpy={spy}
            src="//:0"
            minHeight={300}
            minWidth={600} />
    );
});
*/

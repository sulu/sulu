/* eslint-disable flowtype/require-valid-file-annotation */
import {mount} from 'enzyme';
import React from 'react';
import {ImageRectangleSelection} from '../ImageRectangleSelection';

jest.mock('../../../services/DOM/afterElementsRendered');

jest.mock('../../withContainerSize/withContainerSize');

class MockedImageSelection extends ImageRectangleSelection {
    componentDidMount() {
        Promise.resolve().then(this.props.mountSpy);
    }

    constructor(props) {
        super(props);

        this.image = {naturalWidth: 1920, naturalHeight: 1080};
        this.imageLoaded = true;
    }
}

test('The component should render with image source', () => {
    const view = mount(<MockedImageSelection containerHeight={360} containerWidth={640} src="//:0" />);
    expect(view.render()).toMatchSnapshot();
});

test('The component should calculate the selection with respect to the image', (done) => {
    const onChangeSpy = jest.fn((data) => {
        expect(data).toEqual({width: 1920, height: 1080, top: 0, left: 0});
        done();
    });

    mount(
        <MockedImageSelection
            containerHeight={360}
            containerWidth={640}
            onChange={onChangeSpy}
            src="//:0"
        />
    );
});

test('The component should render with initial selection', (done) => {
    const spy = jest.fn(() => {
        expect(view.render()).toMatchSnapshot();
        done();
    });
    const onChangeSpy = jest.fn((data) => {
        expect(data).toEqual({width: 1500, height: 800, top: 200, left: 300});
    });

    const view = mount(
        <MockedImageSelection
            containerHeight={360}
            containerWidth={640}
            initialSelection={{width: 1500, height: 800, top: 200, left: 300}}
            mountSpy={spy}
            onChange={onChangeSpy}
            src="//:0"
        />
    );
});

test('The component should render with minWidth and minHeight', (done) => {
    const spy = jest.fn(() => {
        const rectangle = view.find('RectangleSelection');
        expect(rectangle.length).toBe(1);
        expect(rectangle.props().minWidth).toBe(200);
        expect(rectangle.props().minHeight).toBe(100);
        done();
    });

    const view = mount(
        <MockedImageSelection
            containerHeight={360}
            containerWidth={640}
            minHeight={300}
            minWidth={600}
            mountSpy={spy}
            src="//:0"
        />
    );
});

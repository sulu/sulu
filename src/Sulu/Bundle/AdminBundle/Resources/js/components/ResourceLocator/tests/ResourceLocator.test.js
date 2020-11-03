// @flow
import React from 'react';
import {render, mount, shallow} from 'enzyme';
import {observable} from 'mobx';
import ResourceLocator from '../ResourceLocator';

test('ResourceLocator should render with type full', () => {
    const onChange = jest.fn();
    const value = '/parent';
    const locale = observable.box('en');

    expect(
        render(<ResourceLocator locale={locale} mode="full" onBlur={jest.fn()} onChange={onChange} value={value} />)
    ).toMatchSnapshot();
});

test('ResourceLocator should render with type full and a value of undefined', () => {
    const onChange = jest.fn();
    const locale = observable.box('en');

    expect(
        render(<ResourceLocator locale={locale} mode="full" onBlur={jest.fn()} onChange={onChange} value={undefined} />)
    ).toMatchSnapshot();
});

test('ResourceLocator should render with type leaf', () => {
    const onChange = jest.fn();
    const value = '/parent/child';
    const locale = observable.box('en');

    expect(
        render(<ResourceLocator locale={locale} mode="leaf" onBlur={jest.fn()} onChange={onChange} value={value} />)
    ).toMatchSnapshot();
});

test('ResourceLocator should render with type leaf and a value of undefined', () => {
    const onChange = jest.fn();
    const locale = observable.box('en');

    expect(
        render(<ResourceLocator locale={locale} mode="leaf" onBlur={jest.fn()} onChange={onChange} value={undefined} />)
    ).toMatchSnapshot();
});

test('ResourceLocator should render when disabled', () => {
    const onChange = jest.fn();
    const value = '/parent';
    const locale = observable.box('en');

    expect(
        render(
            <ResourceLocator
                disabled={true}
                locale={locale}
                mode="full"
                onBlur={jest.fn()}
                onChange={onChange}
                value={value}
            />
        )
    ).toMatchSnapshot();
});

test('ResourceLocator should update the split leaf representation when value changes', () => {
    const locale = observable.box('en');
    const resourceLocator = mount(
        <ResourceLocator locale={locale} mode="leaf" onBlur={jest.fn()} onChange={jest.fn()} value="/child" />
    );

    expect(resourceLocator.find('.fixed').prop('children')).toEqual('/');
    expect(resourceLocator.find('Input').prop('value')).toEqual('child');

    resourceLocator.setProps({value: '/child/test'});
    resourceLocator.update();
    expect(resourceLocator.find('.fixed').prop('children')).toEqual('/child/');
    expect(resourceLocator.find('Input').prop('value')).toEqual('test');
});

test('ResourceLocator should call the onChange callback when the input changes with type full', () => {
    const onChange = jest.fn();
    const value = '/parent';
    const locale = observable.box('en');
    const resourceLocator = mount(
        <ResourceLocator locale={locale} mode="full" onBlur={jest.fn()} onChange={onChange} value={value} />
    );
    resourceLocator.find('Input').props().onChange('parent-new');
    expect(onChange).toHaveBeenCalledWith('/parent-new');
});

test('ResourceLocator should call the onChange callback when the input changes with type leaf', () => {
    const onChange = jest.fn();
    const value = '/parent/child';
    const locale = observable.box('en');
    const resourceLocator = mount(
        <ResourceLocator locale={locale} mode="leaf" onBlur={jest.fn()} onChange={onChange} value={value} />
    );
    resourceLocator.find('Input').props().onChange('child-new');
    expect(onChange).toHaveBeenCalledWith('/parent/child-new');
});

test('ResourceLocator should call the onChange callback and replace a typed slash with a dash in leaf mode', () => {
    const onChange = jest.fn();
    const value = '/parent/child';
    const locale = observable.box('en');
    const resourceLocator = mount(
        <ResourceLocator locale={locale} mode="leaf" onBlur={jest.fn()} onChange={onChange} value={value} />
    );
    resourceLocator.find('Input').props().onChange('child/');
    expect(onChange).toBeCalledWith('/parent/child-');
});

test('ResourceLocator should replace capital letters with lower case in leaf mode before calling onChange', () => {
    const onChange = jest.fn();
    const value = '/parent/child';
    const locale = observable.box('en');
    const resourceLocator = mount(
        <ResourceLocator locale={locale} mode="leaf" onBlur={jest.fn()} onChange={onChange} value={value} />
    );
    resourceLocator.find('Input').props().onChange('CHILD');
    expect(onChange).toBeCalledWith('/parent/child');
});

test('ResourceLocator should replace capital letters with lower case in full mode before calling onChange', () => {
    const onChange = jest.fn();
    const value = '/parent/child';
    const locale = observable.box('en');
    const resourceLocator = mount(
        <ResourceLocator locale={locale} mode="full" onBlur={jest.fn()} onChange={onChange} value={value} />
    );
    resourceLocator.find('Input').props().onChange('parent/CHILD');
    expect(onChange).toBeCalledWith('/parent/child');
});

test('ResourceLocator should call the onChange callback when a slash is typed in full mode', () => {
    const onChange = jest.fn();
    const value = '/parent/child';
    const locale = observable.box('en');
    const resourceLocator = mount(
        <ResourceLocator locale={locale} mode="full" onBlur={jest.fn()} onChange={onChange} value={value} />
    );
    resourceLocator.find('Input').props().onChange('parent/child/');
    expect(onChange).toBeCalledWith('/parent/child/');
});

test('ResourceLocator should call the onChange callback with undefined if no input is given', () => {
    const onChange = jest.fn();
    const locale = observable.box('en');
    const resourceLocator = mount(<ResourceLocator locale={locale} mode="leaf" onChange={onChange} value="/url" />);
    resourceLocator.find('Input').prop('onChange')(undefined);
    expect(onChange).toHaveBeenCalledWith(undefined);
});

test('ResourceLocator should call the onChange callback and replace "/" with "-"', () => {
    const onChange = jest.fn();

    const value = '/parent';
    const locale = observable.box('en');
    const resourceLocator = mount(
        <ResourceLocator locale={locale} mode="full" onBlur={jest.fn()} onChange={onChange} value={value} />
    );
    resourceLocator.find('Input').props().onChange('parent/child/');
    expect(onChange).toBeCalledWith('/parent/child/');
});

test('ResourceLocator should call the onBlur callback when the Input finishes editing', () => {
    const finishSpy = jest.fn();
    const locale = observable.box('en');

    const resourceLocator = shallow(
        <ResourceLocator locale={locale} mode="leaf" onBlur={finishSpy} onChange={jest.fn()} value="/some/url" />
    );

    resourceLocator.find('Input').simulate('blur');

    expect(finishSpy).toBeCalledWith();
});

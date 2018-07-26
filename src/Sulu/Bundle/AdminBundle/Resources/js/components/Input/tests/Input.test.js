// @flow
import React from 'react';
import {render, mount, shallow} from 'enzyme';
import Input from '../Input';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

test('Input should render', () => {
    const onChange = jest.fn();
    expect(render(<Input value="My value" onChange={onChange} onBlur={jest.fn()} />)).toMatchSnapshot();
});

test('Input should render with invalid value', () => {
    const onChange = jest.fn();
    expect(render(<Input valid={false} value="My value" onChange={onChange} onBlur={jest.fn()} />)).toMatchSnapshot();
});

test('Input should render with icon', () => {
    const onChange = jest.fn();
    expect(render(<Input icon="su-pen" value="My value" onChange={onChange} onBlur={jest.fn()} />)).toMatchSnapshot();
});

test('Input should render with type', () => {
    const onChange = jest.fn();
    expect(render(
        <Input type="password" value="My value" onChange={onChange} onBlur={jest.fn()} />
    )).toMatchSnapshot();
});

test('Input should render with placeholder', () => {
    const onChange = jest.fn();
    expect(render(
        <Input placeholder="My placeholder" value="My value" onChange={onChange} onBlur={jest.fn()} />
    )).toMatchSnapshot();
});

test('Input should render with value', () => {
    const onChange = jest.fn();
    expect(render(<Input value="My value" onChange={onChange} onBlur={jest.fn()} />)).toMatchSnapshot();
});

test('Input should render undefined value as empty string', () => {
    const onChange = jest.fn();
    expect(render(<Input value={undefined} onChange={onChange} onBlur={jest.fn()} />)).toMatchSnapshot();
});

test('Input should render with a character counter', () => {
    expect(render(<Input value="asdf" onChange={jest.fn()} onBlur={jest.fn()} maxCharacters={2} />)).toMatchSnapshot();
});

test('Input should render with a segment counter', () => {
    expect(render(
        <Input
            value="keyword1, keyword2"
            onChange={jest.fn()}
            onBlur={jest.fn()}
            maxSegments={3}
            segmentDelimiter=","
        />
    )).toMatchSnapshot();
});

test('Input should call the callback when the input changes', () => {
    const onChange = jest.fn();
    const input = shallow(<Input value="My value" onChange={onChange} onBlur={jest.fn()} />);
    const event = {currentTarget: {value: 'my-value'}};
    input.find('input').simulate('change', event);
    expect(onChange).toHaveBeenCalledWith('my-value', event);
});

test('Input should call the callback with undefined if the input value is removed', () => {
    const onChange = jest.fn();
    const input = shallow(<Input value="My value" onChange={onChange} onBlur={jest.fn()} />);
    const event = {currentTarget: {value: ''}};
    input.find('input').simulate('change', event);
    expect(onChange).toHaveBeenCalledWith(undefined, event);
});

test('Input should call the callback when icon was clicked', () => {
    const onChange = jest.fn();
    const handleIconClick = jest.fn();
    const input = mount(<Input icon="su-pen" value="My value" onChange={onChange} onIconClick={handleIconClick} />);
    input.find('Icon').simulate('click');
    expect(handleIconClick).toHaveBeenCalled();
});

test('Input should render with a loader', () => {
    const onChange = jest.fn();
    expect(render(<Input value={undefined} loading={true} onChange={onChange} onBlur={jest.fn()} />)).toMatchSnapshot();
});

test('Input should render collapsed', () => {
    expect(render(<Input value={undefined} onChange={jest.fn()} collapsed={true} />)).toMatchSnapshot();
});

test('Input should render append container when onClearClick callback is provided', () => {
    expect(render(<Input value={undefined} onChange={jest.fn()} onClearClick={jest.fn()} />)).toMatchSnapshot();
});

test('Input should render append container with icon when onClearClick callback is provided and value is set', () => {
    expect(render(<Input value="test" onChange={jest.fn()} onClearClick={jest.fn()} />)).toMatchSnapshot();
});

test('Input should should call the callback when clear icon was clicked', () => {
    const onClearClick = jest.fn();
    const input = mount(<Input value="My value" onChange={jest.fn()} onClearClick={onClearClick} />);
    input.find('Icon').simulate('click');
    expect(onClearClick).toHaveBeenCalled();
});

test('Input should render with dark skin', () => {
    expect(
        render(<Input icon="su-pen" value={undefined} onChange={jest.fn()} onClearClick={jest.fn()} skin="dark" />)
    ).toMatchSnapshot();
});

test('Input should render with type number with attributes', () => {
    expect(render(
        <Input type="number" value={25} onChange={jest.fn()} onBlur={jest.fn()} min={10} max={50} step={5} />)
    ).toMatchSnapshot();
});

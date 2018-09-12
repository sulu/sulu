// @flow
import React from 'react';
import {render, mount, shallow} from 'enzyme';
import Input from '../Input';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

test('Input should render', () => {
    const onChange = jest.fn();
    expect(render(<Input onBlur={jest.fn()} onChange={onChange} value="My value" />)).toMatchSnapshot();
});

test('Input should render with invalid value', () => {
    const onChange = jest.fn();
    expect(render(<Input onBlur={jest.fn()} onChange={onChange} valid={false} value="My value" />)).toMatchSnapshot();
});

test('Input should render with icon', () => {
    const onChange = jest.fn();
    expect(render(<Input icon="su-pen" onBlur={jest.fn()} onChange={onChange} value="My value" />)).toMatchSnapshot();
});

test('Input should render with inputmode', () => {
    const onChange = jest.fn();
    expect(render(
        <Input inputMode="numeric" onBlur={jest.fn()} onChange={onChange} value="My value" />
    )).toMatchSnapshot();
});

test('Input should render with type', () => {
    const onChange = jest.fn();
    expect(render(
        <Input onBlur={jest.fn()} onChange={onChange} type="password" value="My value" />
    )).toMatchSnapshot();
});

test('Input should render with placeholder', () => {
    const onChange = jest.fn();
    expect(render(
        <Input onBlur={jest.fn()} onChange={onChange} placeholder="My placeholder" value="My value" />
    )).toMatchSnapshot();
});

test('Input should render with value', () => {
    const onChange = jest.fn();
    expect(render(<Input onBlur={jest.fn()} onChange={onChange} value="My value" />)).toMatchSnapshot();
});

test('Input should render undefined value as empty string', () => {
    const onChange = jest.fn();
    expect(render(<Input onBlur={jest.fn()} onChange={onChange} value={undefined} />)).toMatchSnapshot();
});

test('Input should render with a character counter', () => {
    expect(render(<Input maxCharacters={2} onBlur={jest.fn()} onChange={jest.fn()} value="asdf" />)).toMatchSnapshot();
});

test('Input should render with a segment counter', () => {
    expect(render(
        <Input
            maxSegments={3}
            onBlur={jest.fn()}
            onChange={jest.fn()}
            segmentDelimiter=","
            value="keyword1, keyword2"
        />
    )).toMatchSnapshot();
});

test('Input should call the callback when the input changes', () => {
    const onChange = jest.fn();
    const input = shallow(<Input onBlur={jest.fn()} onChange={onChange} value="My value" />);
    const event = {currentTarget: {value: 'my-value'}};
    input.find('input').simulate('change', event);
    expect(onChange).toHaveBeenCalledWith('my-value', event);
});

test('Input should call the callback with undefined if the input value is removed', () => {
    const onChange = jest.fn();
    const input = shallow(<Input onBlur={jest.fn()} onChange={onChange} value="My value" />);
    const event = {currentTarget: {value: ''}};
    input.find('input').simulate('change', event);
    expect(onChange).toHaveBeenCalledWith(undefined, event);
});

test('Input should call the callback when icon was clicked', () => {
    const onChange = jest.fn();
    const handleIconClick = jest.fn();
    const input = mount(<Input icon="su-pen" onChange={onChange} onIconClick={handleIconClick} value="My value" />);
    input.find('Icon').simulate('click');
    expect(handleIconClick).toHaveBeenCalled();
});

test('Input should render with a loader', () => {
    const onChange = jest.fn();
    expect(render(<Input loading={true} onBlur={jest.fn()} onChange={onChange} value={undefined} />)).toMatchSnapshot();
});

test('Input should render collapsed', () => {
    expect(render(<Input collapsed={true} onChange={jest.fn()} value={undefined} />)).toMatchSnapshot();
});

test('Input should render append container when onClearClick callback is provided', () => {
    expect(render(<Input onChange={jest.fn()} onClearClick={jest.fn()} value={undefined} />)).toMatchSnapshot();
});

test('Input should render append container with icon when onClearClick callback is provided and value is set', () => {
    expect(render(<Input onChange={jest.fn()} onClearClick={jest.fn()} value="test" />)).toMatchSnapshot();
});

test('Input should should call the callback when clear icon was clicked', () => {
    const onClearClick = jest.fn();
    const input = mount(<Input onChange={jest.fn()} onClearClick={onClearClick} value="My value" />);
    input.find('Icon').simulate('click');
    expect(onClearClick).toHaveBeenCalled();
});

test('Input should render with dark skin', () => {
    expect(
        render(<Input icon="su-pen" onChange={jest.fn()} onClearClick={jest.fn()} skin="dark" value={undefined} />)
    ).toMatchSnapshot();
});

test('Input should render with type number with attributes', () => {
    expect(render(
        <Input max={50} min={10} onBlur={jest.fn()} onChange={jest.fn()} step={5} type="number" value={25} />)
    ).toMatchSnapshot();
});

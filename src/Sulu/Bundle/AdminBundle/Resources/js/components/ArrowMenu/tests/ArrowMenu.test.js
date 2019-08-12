// @flow
import React from 'react';
import {mount} from 'enzyme';
import ArrowMenu from '../ArrowMenu';

afterEach(() => {
    if (document.body) {
        document.body.innerHTML = '';
    }
});

test('Render ArrowMenu closed', () => {
    const handleClose = jest.fn();
    const handleChangeSection1 = jest.fn();
    const handleChangeSection2 = jest.fn();
    const open = false;
    const button = (<button>Nice button</button>);
    const value1 = 'sulu';
    const value2 = undefined;

    const arrowMenu = mount(
        <ArrowMenu anchorElement={button} onClose={handleClose} open={open}>
            <ArrowMenu.Section title="Search Section">
                <input type="text" />
            </ArrowMenu.Section>
            <ArrowMenu.SingleItemSection
                icon="su-webspace"
                onChange={handleChangeSection1}
                title="Webspaces"
                value={value1}
            >
                <ArrowMenu.Item value="sulu">Sulu</ArrowMenu.Item>
                <ArrowMenu.Item value="sulu_blog">Sulu Blog</ArrowMenu.Item>
                <ArrowMenu.Item value="sulu_doc">Sulu Doc</ArrowMenu.Item>
            </ArrowMenu.SingleItemSection>
            <ArrowMenu.SingleItemSection
                icon="su-check"
                onChange={handleChangeSection2}
                title="Columns"
                value={value2}
            >
                <ArrowMenu.Item value="title">Title</ArrowMenu.Item>
                <ArrowMenu.Item value="description">Description</ArrowMenu.Item>
            </ArrowMenu.SingleItemSection>
            <ArrowMenu.Section>
                <ArrowMenu.Action onClick={jest.fn()}>Test Action 1</ArrowMenu.Action>
                <ArrowMenu.Action onClick={jest.fn()}>Test Action 2</ArrowMenu.Action>
                <ArrowMenu.Action onClick={jest.fn()}>Test Action 3</ArrowMenu.Action>
            </ArrowMenu.Section>
        </ArrowMenu>
    );

    expect(arrowMenu.children()).toHaveLength(2);
    expect(arrowMenu.find('ArrowMenu > button').text()).toEqual('Nice button');

    expect(arrowMenu.find('Popover').prop('open')).toEqual(false);
});

test('Render ArrowMenu with non-HTML element as anchor', () => {
    class Button extends React.Component<*> {
        render() {
            return <button ref={this.props.buttonRef} />;
        }
    }

    const arrowMenu = mount(
        <ArrowMenu anchorElement={<Button />} open={true} refProp="buttonRef">
            <ArrowMenu.Item value="title">Title</ArrowMenu.Item>
        </ArrowMenu>
    );

    expect(arrowMenu.render()).toMatchSnapshot();
});

test('Render ArrowMenu open', () => {
    const handleClose = jest.fn();
    const handleChangeSection1 = jest.fn();
    const handleChangeSection2 = jest.fn();
    const open = true;
    const button = (<button>Nice button</button>);
    const value1 = 'sulu';
    const value2 = undefined;

    const arrowMenu = mount(
        <ArrowMenu anchorElement={button} onClose={handleClose} open={open}>
            <ArrowMenu.Section title="Search Section">
                <input type="text" />
            </ArrowMenu.Section>
            <ArrowMenu.SingleItemSection
                icon="su-webspace"
                onChange={handleChangeSection1}
                title="Webspaces"
                value={value1}
            >
                <ArrowMenu.Item value="sulu">Sulu</ArrowMenu.Item>
                <ArrowMenu.Item value="sulu_blog">Sulu Blog</ArrowMenu.Item>
                <ArrowMenu.Item value="sulu_doc">Sulu Doc</ArrowMenu.Item>
            </ArrowMenu.SingleItemSection>
            <ArrowMenu.SingleItemSection
                icon="su-check"
                onChange={handleChangeSection2}
                title="Columns"
                value={value2}
            >
                <ArrowMenu.Item value="title">Title</ArrowMenu.Item>
                <ArrowMenu.Item value="description">Description</ArrowMenu.Item>
            </ArrowMenu.SingleItemSection>
            <ArrowMenu.Section>
                <ArrowMenu.Action onClick={jest.fn()}>Test Action 1</ArrowMenu.Action>
                <ArrowMenu.Action onClick={jest.fn()}>Test Action 2</ArrowMenu.Action>
                <ArrowMenu.Action onClick={jest.fn()}>Test Action 3</ArrowMenu.Action>
            </ArrowMenu.Section>
        </ArrowMenu>
    );

    expect(arrowMenu.children()).toHaveLength(2);
    expect(arrowMenu.find('ArrowMenu > button').text()).toEqual('Nice button');

    expect(arrowMenu.find('Popover').children()).toHaveLength(2);
    expect(arrowMenu.find('Popover Backdrop').prop('open')).toEqual(true);
    expect(arrowMenu.render()).toMatchSnapshot();
});

test('Render ArrowMenu open with falsy values', () => {
    const handleClose = jest.fn();
    const handleChangeSection1 = jest.fn();
    const handleChangeSection2 = jest.fn();
    const open = true;
    const button = (<button>Nice button</button>);
    const value1 = 'sulu';
    const value2 = undefined;

    const arrowMenu = mount(
        <ArrowMenu anchorElement={button} onClose={handleClose} open={open}>
            <ArrowMenu.Section title="Search Section">
                <input type="text" />
                {false}
            </ArrowMenu.Section>
            <ArrowMenu.SingleItemSection
                icon="su-webspace"
                onChange={handleChangeSection1}
                title="Webspaces"
                value={value1}
            >
                <ArrowMenu.Item value="sulu">Sulu</ArrowMenu.Item>
                <ArrowMenu.Item value="sulu_blog">Sulu Blog</ArrowMenu.Item>
                <ArrowMenu.Item value="sulu_doc">Sulu Doc</ArrowMenu.Item>
                {false}
            </ArrowMenu.SingleItemSection>
            <ArrowMenu.SingleItemSection
                icon="su-check"
                onChange={handleChangeSection2}
                title="Columns"
                value={value2}
            >
                <ArrowMenu.Item value="title">Title</ArrowMenu.Item>
                <ArrowMenu.Item value="description">Description</ArrowMenu.Item>
                {false}
            </ArrowMenu.SingleItemSection>
            <ArrowMenu.Section>
                <ArrowMenu.Action onClick={jest.fn()}>Test Action 1</ArrowMenu.Action>
                <ArrowMenu.Action onClick={jest.fn()}>Test Action 2</ArrowMenu.Action>
                <ArrowMenu.Action onClick={jest.fn()}>Test Action 3</ArrowMenu.Action>
                {false}
            </ArrowMenu.Section>
            {false}
        </ArrowMenu>
    );

    expect(arrowMenu.children()).toHaveLength(2);
    expect(arrowMenu.find('ArrowMenu > button').text()).toEqual('Nice button');

    expect(arrowMenu.find('Popover').children()).toHaveLength(2);
    expect(arrowMenu.find('Popover Backdrop').prop('open')).toEqual(true);
    expect(arrowMenu.render()).toMatchSnapshot();
});

test('Render the correct item active with a value of undefined', () => {
    const button = (<button>Nice button</button>);

    const arrowMenu = mount(
        <ArrowMenu anchorElement={button} onClose={jest.fn()} open={true}>
            <ArrowMenu.SingleItemSection
                icon="su-webspace"
                onChange={jest.fn()}
                title="Webspaces"
                value={undefined}
            >
                <ArrowMenu.Item value={undefined}>Everything</ArrowMenu.Item>
            </ArrowMenu.SingleItemSection>
        </ArrowMenu>
    );

    expect(arrowMenu.find('Item').at(0).prop('active')).toEqual(true);
});

test('Events should be called correctly', () => {
    const handleClose = jest.fn();
    const handleChangeSection1 = jest.fn();
    const handleChangeSection2 = jest.fn();
    const open = true;
    const button = (<button>Nice button</button>);
    const value1 = 'sulu';
    const value2 = undefined;
    const handleActionClick1 = jest.fn();
    const handleActionClick2 = jest.fn();
    const handleActionClick3 = jest.fn();

    const arrowMenu = mount(
        <ArrowMenu anchorElement={button} onClose={handleClose} open={open}>
            <ArrowMenu.Section title="Search Section">
                <input type="text" />
            </ArrowMenu.Section>
            <ArrowMenu.SingleItemSection
                icon="su-webspace"
                onChange={handleChangeSection1}
                title="Webspaces"
                value={value1}
            >
                <ArrowMenu.Item value="sulu">Sulu</ArrowMenu.Item>
                <ArrowMenu.Item value="sulu_blog">Sulu Blog</ArrowMenu.Item>
                <ArrowMenu.Item value="sulu_doc">Sulu Doc</ArrowMenu.Item>
            </ArrowMenu.SingleItemSection>
            <ArrowMenu.SingleItemSection
                icon="check"
                onChange={handleChangeSection2}
                title="Columns"
                value={value2}
            >
                <ArrowMenu.Item value="title">Title</ArrowMenu.Item>
                <ArrowMenu.Item value="description">Description</ArrowMenu.Item>
            </ArrowMenu.SingleItemSection>
            <ArrowMenu.Section>
                <ArrowMenu.Action onClick={handleActionClick1}>Test Action 1</ArrowMenu.Action>
                <ArrowMenu.Action onClick={handleActionClick2}>Test Action 2</ArrowMenu.Action>
                <ArrowMenu.Action onClick={handleActionClick3}>Test Action 3</ArrowMenu.Action>
            </ArrowMenu.Section>
        </ArrowMenu>
    );

    arrowMenu.find('SingleItemSection').at(0).find('Item').at(1).simulate('click');
    expect(handleChangeSection1).toBeCalledWith('sulu_blog');

    arrowMenu.find('Action').at(1).simulate('click');
    expect(handleActionClick2).toBeCalled();
    expect(handleClose).toBeCalled();

    arrowMenu.find('SingleItemSection').at(1).find('Item').at(0).simulate('click');
    expect(handleChangeSection2).toBeCalledWith('title');

    arrowMenu.find('Backdrop').simulate('click');
    expect(handleClose).toBeCalled();
});

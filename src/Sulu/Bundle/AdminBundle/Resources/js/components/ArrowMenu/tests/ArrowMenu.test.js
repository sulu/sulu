// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import ArrowMenu from '../ArrowMenu';

test('Render ArrowMenu closed', () => {
    const handleClose = jest.fn();
    const handleChangeSection1 = jest.fn();
    const handleChangeSection2 = jest.fn();

    render(
        <ArrowMenu anchorElement={<button type="button">Nice button</button>} onClose={handleClose} open={false}>
            <ArrowMenu.Section title="Search Section">
                <input type="text" />
            </ArrowMenu.Section>
            <ArrowMenu.SingleItemSection
                icon="su-webspace"
                onChange={handleChangeSection1}
                title="Webspaces"
                value="sulu"
            >
                <ArrowMenu.Item value="sulu">Sulu</ArrowMenu.Item>
                <ArrowMenu.Item value="sulu_blog">Sulu Blog</ArrowMenu.Item>
                <ArrowMenu.Item value="sulu_doc">Sulu Doc</ArrowMenu.Item>
            </ArrowMenu.SingleItemSection>
            <ArrowMenu.SingleItemSection
                icon="su-check"
                onChange={handleChangeSection2}
                title="Columns"
                value={undefined}
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

    const anchorButton = screen.getByText('Nice button');
    expect(anchorButton).toBeInTheDocument();

    const menuSection = screen.queryByText('Webspaces');
    expect(menuSection).not.toBeInTheDocument();
});

test('Render ArrowMenu with non-HTML element as anchor', () => {
    class Button extends React.Component<*> {
        render() {
            return <button ref={this.props.buttonRef} type="button" />;
        }
    }

    const {baseElement} = render(
        <ArrowMenu anchorElement={<Button />} open={true} refProp="buttonRef">
            <ArrowMenu.Item value="title">Title</ArrowMenu.Item>
        </ArrowMenu>
    );

    expect(baseElement).toMatchSnapshot();
});

test('Render ArrowMenu open', () => {
    const handleClose = jest.fn();
    const handleChangeSection1 = jest.fn();
    const handleChangeSection2 = jest.fn();

    const {baseElement} = render(
        <ArrowMenu anchorElement={<button type="button">Nice button</button>} onClose={handleClose} open={true}>
            <ArrowMenu.Section title="Search Section">
                <input type="text" />
            </ArrowMenu.Section>
            <ArrowMenu.SingleItemSection
                icon="su-webspace"
                onChange={handleChangeSection1}
                title="Webspaces"
                value="sulu"
            >
                <ArrowMenu.Item value="sulu">Sulu</ArrowMenu.Item>
                <ArrowMenu.Item value="sulu_blog">Sulu Blog</ArrowMenu.Item>
                <ArrowMenu.Item value="sulu_doc">Sulu Doc</ArrowMenu.Item>
            </ArrowMenu.SingleItemSection>
            <ArrowMenu.SingleItemSection
                icon="su-check"
                onChange={handleChangeSection2}
                title="Columns"
                value={undefined}
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

    const anchorButton = screen.getByText('Nice button');
    expect(anchorButton).toBeInTheDocument();

    const menuSection = screen.queryByText('Webspaces');
    expect(menuSection).toBeInTheDocument();

    expect(baseElement).toMatchSnapshot();
});

test('Render ArrowMenu open with falsy values', () => {
    const handleClose = jest.fn();
    const handleChangeSection1 = jest.fn();
    const handleChangeSection2 = jest.fn();

    const {baseElement} = render(
        <ArrowMenu anchorElement={<button type="button">Nice button</button>} onClose={handleClose} open={true}>
            <ArrowMenu.Section title="Search Section">
                <input type="text" />
                {false}
            </ArrowMenu.Section>
            <ArrowMenu.SingleItemSection
                icon="su-webspace"
                onChange={handleChangeSection1}
                title="Webspaces"
                value="sulu"
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
                value={undefined}
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

    const anchorButton = screen.getByText('Nice button');
    expect(anchorButton).toBeInTheDocument();

    const menuSection = screen.queryByText('Webspaces');
    expect(menuSection).toBeInTheDocument();

    expect(baseElement).toMatchSnapshot();
});

test('Events should be called correctly', async() => {
    const handleClose = jest.fn();
    const handleChangeSection1 = jest.fn();
    const handleChangeSection2 = jest.fn();
    const handleActionClick1 = jest.fn();
    const handleActionClick2 = jest.fn();
    const handleActionClick3 = jest.fn();

    render(
        <ArrowMenu anchorElement={<button type="button">Nice button</button>} onClose={handleClose} open={true}>
            <ArrowMenu.Section title="Search Section">
                <input type="text" />
            </ArrowMenu.Section>
            <ArrowMenu.SingleItemSection
                icon="su-webspace"
                onChange={handleChangeSection1}
                title="Webspaces"
                value="sulu"
            >
                <ArrowMenu.Item value="sulu">Sulu</ArrowMenu.Item>
                <ArrowMenu.Item value="sulu_blog">Sulu Blog</ArrowMenu.Item>
                <ArrowMenu.Item value="sulu_doc">Sulu Doc</ArrowMenu.Item>
            </ArrowMenu.SingleItemSection>
            <ArrowMenu.SingleItemSection
                icon="check"
                onChange={handleChangeSection2}
                title="Columns"
                value={undefined}
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

    const user = userEvent.setup();
    await user.click(screen.getByText('Sulu Blog'));
    expect(handleChangeSection1).toBeCalledWith('sulu_blog');

    await user.click(screen.getByText('Test Action 2'));
    expect(handleActionClick2).toBeCalled();
    expect(handleClose).toBeCalledTimes(1);

    await user.click(screen.getByText('Title'));
    expect(handleChangeSection2).toBeCalledWith('title');

    await user.click(screen.getByTestId('backdrop'));
    expect(handleClose).toBeCalledTimes(2);
});

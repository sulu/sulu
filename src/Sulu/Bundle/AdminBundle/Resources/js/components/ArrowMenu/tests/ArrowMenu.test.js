// @flow
import React from 'react';
import {render, mount, shallow} from 'enzyme';
import ArrowMenu from '../ArrowMenu';

test('Render ItemSection', () => {
    const handleClose = jest.fn();
    const handleChangeSection1 = jest.fn();
    const handleChangeSection2 = jest.fn();
    const open = false;
    const button = (<button>Nice button</button>);
    const value1 = 'sulu';
    const value2 = undefined;

    expect(render(
        <ArrowMenu open={open} onClose={handleClose} anchorElement={button}>
            <ArrowMenu.Section title="Search Section">
                <input type="text" />
            </ArrowMenu.Section>
            <ArrowMenu.ItemSection icon="dot-circle-o" title="Webspaces" value={value1} onChange={handleChangeSection1}>
                <ArrowMenu.Item value="sulu">Sulu</ArrowMenu.Item>
                <ArrowMenu.Item value="sulu_blog">Sulu Blog</ArrowMenu.Item>
                <ArrowMenu.Item value="sulu_doc">Sulu Doc</ArrowMenu.Item>
            </ArrowMenu.ItemSection>
            <ArrowMenu.ItemSection icon="check" title="Columns" value={value2} onChange={handleChangeSection2}>
                <ArrowMenu.Item value="title">Title</ArrowMenu.Item>
                <ArrowMenu.Item value="description">Description</ArrowMenu.Item>
            </ArrowMenu.ItemSection>
        </ArrowMenu>
    )).toMatchSnapshot();
});

test('Events should be called correctly', () => {
    const body = document.body;

    const handleClose = jest.fn();
    const handleChangeSection1 = jest.fn();
    const handleChangeSection2 = jest.fn();
    const open = true;
    const button = (<button>Nice button</button>);
    const value1 = 'sulu';
    const value2 = undefined;

    const arrowMenu = mount(
        <ArrowMenu open={open} onClose={handleClose} anchorElement={button}>
            <ArrowMenu.Section title="Search Section">
                <input type="text" />
            </ArrowMenu.Section>
            <ArrowMenu.ItemSection icon="dot-circle-o" title="Webspaces" value={value1} onChange={handleChangeSection1}>
                <ArrowMenu.Item value="sulu">Sulu</ArrowMenu.Item>
                <ArrowMenu.Item value="sulu_blog">Sulu Blog</ArrowMenu.Item>
                <ArrowMenu.Item value="sulu_doc">Sulu Doc</ArrowMenu.Item>
            </ArrowMenu.ItemSection>
            <ArrowMenu.ItemSection icon="check" title="Columns" value={value2} onChange={handleChangeSection2}>
                <ArrowMenu.Item value="title">Title</ArrowMenu.Item>
                <ArrowMenu.Item value="description">Description</ArrowMenu.Item>
            </ArrowMenu.ItemSection>
        </ArrowMenu>
    );

    console.log(ArrowMenu.debug());
});

/* eslint-disable flowtype/require-valid-file-annotation */

import OverlayListPositioner from '../OverlayListPositioner';

test('The positioner should return the correct dimensions when the list fits into the screen', () => {
    expect(OverlayListPositioner.getCroppedDimensions(
        500, //  list height
        300, //  list width
        100, //  centered child relative top
        400, //  anchor top
        500, //  anchor left
        500, //  anchor width
        30, //   anchor height
        1920, // window width
        1000, // window height
    )).toEqual({top: 302, left: 480, height: 500, scrollTop: 0});
});

test('The positioner should return the correct dimensions when the list needs to be cropped at the top', () => {
    expect(OverlayListPositioner.getCroppedDimensions(
        500, //  list height
        300, //  list width
        300, //  centered child relative top
        50, //   anchor top
        500, //  anchor left
        500, //  anchor width
        30, //   anchor height
        1920, // window width
        1000, // window height
    )).toEqual({top: 10, left: 480, height: 242, scrollTop: 258});
});

test('The positioner should return the correct dimensions when the list undercuts the min height at the top', () => {
    expect(OverlayListPositioner.getCroppedDimensions(
        500, //  list height
        300, //  list width
        450, //  centered child relative top
        50, //   anchor top
        500, //  anchor left
        500, //  anchor width
        30, //   anchor height
        1920, // window width
        1000, // window height
    )).toEqual({top: 52, left: 480, height: 500, scrollTop: 0});
});

test('The positioner should return the correct dimensions when the list needs to be cropped at the bottom', () => {
    expect(OverlayListPositioner.getCroppedDimensions(
        500, //  list height
        300, //  list width
        300, //  centered child relative top
        920, //  anchor top
        500, //  anchor left
        500, //  anchor width
        30, //   anchor height
        1920, // window width
        1000, // window height
    )).toEqual({top: 622, left: 480, height: 368, scrollTop: 0});
});

test('The positioner should return the correct dimensions when the list undercuts the min height at the bottom', () => {
    expect(OverlayListPositioner.getCroppedDimensions(
        500, //  list height
        300, //  list width
        10, //   centered child relative top
        920, //  anchor top
        500, //  anchor left
        500, //  anchor width
        30, //   anchor height
        1920, // window width
        1000, // window height
    )).toEqual({top: 448, left: 480, height: 500, scrollTop: 0});
});

test('The positioner should return the correct dimensions when the list overflows to the left', () => {
    expect(OverlayListPositioner.getCroppedDimensions(
        500, //  list height
        600, //  list width
        100, //  centered child relative top
        400, //  anchor top
        10, //   anchor left
        500, //  anchor width
        30, //   anchor height
        1920, // window width
        1000, // window height
    )).toEqual({top: 302, left: 10, height: 500, scrollTop: 0});
});

test('The positioner should return the correct dimensions when the list overflows to the right', () => {
    expect(OverlayListPositioner.getCroppedDimensions(
        500, //  list height
        600, //  list width
        100, //  centered child relative top
        400, //  anchor top
        2000, // anchor left
        500, //  anchor width
        30, //   anchor height
        1920, // window width
        1000, // window height
    )).toEqual({top: 302, left: 1310, height: 500, scrollTop: 0});
});

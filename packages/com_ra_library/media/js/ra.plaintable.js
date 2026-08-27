/**
 * Past Walks / Routes - Table (no map): a plain sortable table, built the
 * same way ra.display.tableList (ramblerstable.js) builds its own List
 * tab's table - same ra.paginatedTable engine, same data.list.items
 * column format (see DisplayHelper::renderItemsTable() /
 * Leaflet\Table\Columns\Column) - just without any of that component's
 * Map tab machinery.
 *
 * A separate class rather than reusing ra.display.tableList directly: that
 * component's load() unconditionally tries to build a Leaflet map into its
 * Map tab even when there's no location data for it to plot (the tab gets
 * disabled, but its container is never created, so building anything into
 * it fails). Rather than change that shared component - also used by the
 * existing generic SQL/JSON/CSV table display options - a record type/
 * display combination with genuinely no map to show just uses this
 * instead.
 *
 * @since 1.0.0
 */
var ra;
if (typeof (ra) === "undefined") {
    ra = {};
}
if (typeof (ra.display) === "undefined") {
    ra.display = {};
}
ra.display.plainTable = function (options, data) {
    this.options = options;
    this.data = data;
    this.items = this.data.list.items;
    this.numberOfRows = this.items.length ? this.items[0].values.length : 0;

    this.load = function () {
        var masterdiv = document.getElementById(options.divId);
        var table = new ra.paginatedTable(masterdiv);
        this._setTableHeading(table);
        this._addTableRows(table);
        table.tableEnd();
    };

    this._setTableHeading = function (table) {
        var format = [];
        this.items.forEach((item) => {
            if (item.table) {
                var formatItem = {
                    title: item.name,
                    options: {align: item.align},
                    field: {type: item.type, filter: item.filter, sort: item.sort}
                };
                switch (item.type) {
                    case 'link':
                    case 'textlink':
                    case 'exturl':
                        formatItem.field.type = 'text';
                        break;
                    case 'datetime':
                        formatItem.field.type = 'date';
                }
                format.push(formatItem);
                item.format = formatItem;
            }
        });
        table.tableHeading(format);
    };

    this._addTableRows = function (table) {
        var no, index, item;
        for (no = 0; no < this.numberOfRows; ++no) {
            table.tableRowStart();
            for (index = 0; index < this.items.length; ++index) {
                item = this.items[index];
                if (item.table) {
                    var value = this.displayItem(null, item.type, item.values[no]);
                    table.tableRowItem(value, item.format);
                }
            }
            table.tableRowEnd();
        }
    };

    this.displayItem = function (name, type, value) {
        var out = "";
        if (value === "") {
            return out;
        }
        if (name !== null)
            out = "<b>" + name + ": </b>";
        switch (type) {
            case 'link':
                return out + '<a href="' + value + '" target="_blank">Link</a><br/>';
            case 'textlink':
                return out + '<a href="' + value + '" target="_blank">' + value + '</a><br/>';
            case 'exturl':
                if (!value.includes("://")) {
                    return out + '<a href="https://' + value + '" target="_blank">' + value + '</a><br/>';
                }
                return out + '<a href="' + value + '" target="_blank">' + value + '</a><br/>';
            default:
                return out + value + '<br/>';
        }
    };
};

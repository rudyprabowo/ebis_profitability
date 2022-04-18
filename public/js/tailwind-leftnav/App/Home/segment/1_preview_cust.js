$(function(){
    // Checks if browser is supported
    if (!mxClient.isBrowserSupported())
    {
        // Displays an error message if the browser is
        // not supported.
        mxUtils.error('Browser is not supported!', 200, false);
    }
    else
    {
        // Creates a wrapper editor around a new graph inside
        // the given container using an XML config for the
        // keyboard bindings
        // let config = mxUtils.load(
        //     'editors/config/keyhandler-commons.xml').
        //         getDocumentElement();
        let editor = new mxEditor();
        const container = document.getElementById("preview-content");
        editor.setGraphContainer(container);
        let graph = editor.graph;
        let model = graph.getModel();

        // Auto-resizes the container
        // graph.border = 80;
        graph.centerZoom = false;
        graph.setTooltips(false);
        graph.setHtmlLabels(true);
        graph.getView().translate = new mxPoint(graph.border/2, graph.border/2);
        // graph.setResizeContainer(true);
        graph.resizeContainer = false;
        graph.graphHandler.setRemoveCellsFromParent(false);
        
        // Enables panning with left mouse button
        graph.panningHandler.useLeftButtonForPanning = true;
        graph.panningHandler.ignoreCell = true;
        graph.container.style.cursor = 'move';
        graph.setPanning(true);

        // Adds zoom buttons in top, left corner
        let buttons = document.createElement('div');
        buttons.style.position = 'absolute';
        buttons.style.overflow = 'visible';

        let bs = graph.getBorderSizes();
        buttons.style.top = (container.offsetTop + bs.y) + 'px';
        buttons.style.left = (container.offsetLeft + bs.x) + 'px';
        
        let left = 0;
        let bw = 30;
        let bh = 30;
        
        if (mxClient.IS_QUIRKS)
        {
            bw -= 1;
            bh -= 1;
        }
        
        function addButton(label, funct)
        {
            let btn = document.createElement('div');
            mxUtils.write(btn, label);
            btn.style.position = 'absolute';
            // btn.style.backgroundColor = 'transparent';
            // btn.style.border = '1px solid gray';
            btn.style.textAlign = 'center';
            btn.style.fontSize = '20px';
            btn.style.cursor = 'pointer';
            btn.style.width = bw + 'px';
            btn.style.height = bh + 'px';
            btn.style.left = left + 'px';
            btn.style.top = '0px';
            // console.log(btn.classList);
            btn.classList.add('font-bold');
            btn.classList.add('m-2');
            btn.classList.add('text-white');
            btn.classList.add('bg-ebis-b');
            btn.classList.add('hover:bg-ebis-c');
            btn.classList.add('rounded-sm');
            
            mxEvent.addListener(btn, 'click', function(evt)
            {
                funct();
                mxEvent.consume(evt);
            });
            
            left += bw+5;
            
            buttons.appendChild(btn);
        };
        
        addButton('+', function()
        {
            graph.zoomIn();
        });
        
        addButton('-', function()
        {
            graph.zoomOut();
        });
        
        if (container.nextSibling != null)
        {
            container.parentNode.insertBefore(buttons, container.nextSibling);
        }
        else
        {
            container.appendChild(buttons);
        }

        // Changes the default vertex style in-place
        let style = graph.getStylesheet().getDefaultVertexStyle();
        style[mxConstants.STYLE_SHAPE] = mxConstants.SHAPE_SWIMLANE;
        style[mxConstants.STYLE_RESIZABLE] = 0;
        style[mxConstants.STYLE_VERTICAL_ALIGN] = 'middle';
        style[mxConstants.STYLE_HORIZONTAL] = true;
        // style[mxConstants.STYLE_STARTSIZE] = 40;
        style[mxConstants.STYLE_STROKECOLOR] = null;
        style[mxConstants.STYLE_STROKEWIDTH] = 0;
        style[mxConstants.STYLE_FILLCOLOR] = '#1464BC';
        // style[mxConstants.STYLE_LABEL_BACKGROUNDCOLOR] = '#1464BC';
        style[mxConstants.STYLE_FONTSIZE] = 18;
        style[mxConstants.STYLE_FONTCOLOR] = '#FCFCFC';
        style[mxConstants.STYLE_FONTSTYLE] = 1;
        style[mxConstants.STYLE_SPACING_BOTTOM] = 0;

        //RECT
        style = mxUtils.clone(style);
        style[mxConstants.STYLE_SHAPE] = mxConstants.SHAPE_RECTANGLE;
        style[mxConstants.STYLE_MOVABLE] = 0;
        style[mxConstants.STYLE_FILLCOLOR] = '#FCFCFC';
        style[mxConstants.STYLE_ROUNDED] = false;
        style[mxConstants.STYLE_HORIZONTAL] = true;
        style[mxConstants.STYLE_VERTICAL_ALIGN] = 'middle';
        style[mxConstants.STYLE_STROKECOLOR] = '#1464BC';
        style[mxConstants.STYLE_STROKEWIDTH] = 1;
        style[mxConstants.STYLE_WHITE_SPACE] = 'wrap';
        style[mxConstants.STYLE_FONTSIZE] = 12;
        style[mxConstants.STYLE_FONTSTYLE] = 1;
        style[mxConstants.STYLE_FONTCOLOR] = '#1464BC';
        // delete style[mxConstants.STYLE_SPACING_BOTTOM];
        graph.getStylesheet().putCellStyle('rect-1', style);
        
        style = mxUtils.clone(style);
        style[mxConstants.STYLE_FILLCOLOR] = '#CDDDF8';
        style[mxConstants.STYLE_FONTCOLOR] = '#1464BC';
        graph.getStylesheet().putCellStyle('rect-2', style);
        
        style = mxUtils.clone(style);
        style[mxConstants.STYLE_FILLCOLOR] = '#1464BC';
        style[mxConstants.STYLE_FONTCOLOR] = '#FCFCFC';
        graph.getStylesheet().putCellStyle('rect-3', style);
        
        style = mxUtils.clone(style);
        style[mxConstants.STYLE_FILLCOLOR] = null;
        style[mxConstants.STYLE_FONTCOLOR] = '#1464BC';
        style[mxConstants.STYLE_STROKECOLOR] = null;
        style[mxConstants.STYLE_ALIGN] = "left";
        graph.getStylesheet().putCellStyle('rect-lbl', style);
        
        style = graph.getStylesheet().getDefaultEdgeStyle();
        style[mxConstants.STYLE_EDGE] = mxEdgeStyle.ElbowConnector;
        style[mxConstants.STYLE_ENDARROW] = mxConstants.ARROW_BLOCK;
        style[mxConstants.STYLE_ROUNDED] = true;
        style[mxConstants.STYLE_FONTCOLOR] = '#1464BC';
        style[mxConstants.STYLE_STROKECOLOR] = '#1464BC';
        style[mxConstants.STYLE_STROKEWIDTH] = 4;
        graph.getStylesheet().putCellStyle('arrow-1', style);

        
        style = mxUtils.clone(style);
        style[mxConstants.STYLE_EDGE] = mxEdgeStyle.EntityRelation;
        style[mxConstants.STYLE_STROKEWIDTH] = 2;
        style[mxConstants.STYLE_DASHED] = true;
        graph.getStylesheet().putCellStyle('arrow-2', style);
        
        style = mxUtils.clone(style);
        style[mxConstants.STYLE_DASHED] = false;
        style[mxConstants.STYLE_STROKEWIDTH] = 3;
        style[mxConstants.STYLE_EDGE] = mxEdgeStyle.scalePointArray;
        graph.getStylesheet().putCellStyle('arrow-3', style);
                
        // Installs double click on middle control point and
        // changes style of edges between empty and this value
        // graph.alternateEdgeStyle = 'elbow=vertical';

        // Adds automatic layout and letious switches if the
        // graph is enabled
        if (graph.isEnabled())
        {
            // Allows new connections but no dangling edges
            graph.setConnectable(false);
            graph.setDropEnabled(false);
            graph.setAllowDanglingEdges(false);
            
            // Allows dropping cells into new lanes and
            // lanes into new pools, but disallows dropping
            // cells on edges to split edges
            graph.setDropEnabled(false);
            graph.setSplitEnabled(false);
            
            // Adds new method for identifying a pool
            graph.isPool = function(cell)
            {
                let model = this.getModel();
                let parent = model.getParent(cell);
            
                return parent != null && model.getParent(parent) == model.getRoot();
            };
            
            // Changes swimlane orientation while collapsed
            graph.model.getStyle = function(cell)
            {
                let style = mxGraphModel.prototype.getStyle.apply(this, arguments);
            
                if (graph.isCellCollapsed(cell))
                {
                    if (style != null)
                    {
                        style += ';';
                    }
                    else
                    {
                        style = '';
                    }
                    
                    style += 'horizontal=0;align=left;spacingLeft=14;';
                }
                
                return style;
            };

            // Keeps widths on collapse/expand					
            let foldingHandler = function(sender, evt)
            {
                let cells = evt.getProperty('cells');
                
                for (let i = 0; i < cells.length; i++)
                {
                    let geo = graph.model.getGeometry(cells[i]);

                    if (geo.alternateBounds != null)
                    {
                        geo.width = geo.alternateBounds.width;
                    }
                }
            };

            graph.addListener(mxEvent.FOLD_CELLS, foldingHandler);
        }
        
        // Applies size changes to siblings and parents
        new mxSwimlaneManager(graph);

        // Creates a stack depending on the orientation of the swimlane
        let layout = new mxStackLayout(graph, true);
        
        // Makes sure all children fit into the parent swimlane
        layout.resizeParent = true;
                    
        // Applies the size to children if parent size changes
        layout.fill = true;

        // Only update the size of swimlanes
        layout.isVertexIgnored = function(vertex)
        {
            return !graph.isSwimlane(vertex);
        }
        
        // Keeps the lanes and pools stacked
        let layoutMgr = new mxLayoutManager(graph);

        layoutMgr.getLayout = function(cell)
        {
            if (!model.isEdge(cell) && graph.getModel().getChildCount(cell) > 0 &&
                (model.getParent(cell) == model.getRoot() || graph.isPool(cell)))
            {
                layout.fill = graph.isPool(cell);
                
                return layout;
            }
            
            return null;
        };
        
        // Gets the default parent for inserting new cells. This
        // is normally the first child of the root (ie. layer 0).
        let parent = graph.getDefaultParent();

        // Adds cells to the model in a single step
        model.beginUpdate();
        try
        {
            const line_width = 500;
            const margin_t1 = 40;
            const margin_t2 = 80;
            const margin_x = 80;
            const _x = (line_width/2);
            let y_item = 3;
            const cust_h = 50;
            const cust_w1 = 150;
            const cust_w2 = 70;
            const cust_w3 = 110;
            const cust_width = cust_w1+cust_w2+cust_w3;
            const cust_px = (line_width-cust_width)/2;
            const bl_h = 50;
            const bl_w1 = 150;
            const bl_w2 = 110;
            const bl_w3 = 110;
            const bl2_w1 = 150;
            const bl2_w2 = 110;
            const bl2_w3 = 260;
            const bl_width = bl_w1+bl_w2;
            const bl_px = ((line_width-bl_width)/2)-30;
            const bl_px2 = ((line_width-bl_width)/2)+10;
            const mt_h = 35;
            const mt_w1 = 100;
            const mt_w2 = 100;
            const mt_w3 = 180;
            const mt_width = mt_w1+mt_w2+mt_w3;
            const mt_px = (line_width-mt_width)/2;
            const smt_h = 35;
            const smt_w1 = 100;
            const smt_w2 = 220;
            const smt_width = smt_w1+smt_w2;
            const smt_px = (line_width-smt_width)/2;
            // let pool1 = graph.insertVertex(parent, null, 'Pool 1', 0, 0, 0, 640);
            // pool1.setConnectable(false);

            //VERTEX CUSTOMER
            let lane1 = graph.insertVertex(parent, null, 'CUSTOMER', 0, 0, line_width, (y_item*(cust_h*1.5))+100);
            lane1.setConnectable(false);

            let cust_y = margin_t2;
            let cust_x = cust_px;
            let cust1lbl = graph.insertVertex(lane1, "cust1lbl", 'Nilai KB | % Margin TG (Telkom + Mitra Telkom)', cust_x, cust_y, cust_width, (cust_h/2), 'rect-lbl');
            cust_y += (cust_h/2);
            let cust1a = graph.insertVertex(lane1, "cust1a", "CUSTOMER XXXXXX", cust_x, cust_y, cust_w1, cust_h, 'rect-1');
            cust_x += cust_w1;
            let cust1b = graph.insertVertex(lane1, "cust1b", "9999 PROJECT", cust_x, cust_y, cust_w2, cust_h, 'rect-2');
            cust_x += cust_w2;
            let cust1c = graph.insertVertex(lane1, "cust1c", "999.99M | 99.9%", cust_x, cust_y, cust_w3, cust_h, 'rect-3');
            
            //VERTEX BUSINESS LINE
            let lane2 = graph.insertVertex(parent, null, 'BUSINESS LINE', 0, 0, line_width, (y_item*(bl_h*1.5))+100);
            lane2.setConnectable(false);

            let bl_y = margin_t2;
            let bl_x = bl_px;
            let bl1lbl = graph.insertVertex(lane2, "bl1lbl", 'Nilai KB | % Margin Telkom', bl_x, bl_y, bl_width, (bl_h/2), 'rect-lbl');
            bl_y += (bl_h/2);
            let bl1a = graph.insertVertex(lane2, "bl1a", "CONNECTIVITY", bl_x, bl_y, bl_w1, bl_h, 'rect-1');
            bl_x += bl_w1;
            let bl1b = graph.insertVertex(lane2, "bl1b", "9999 PROJECT", bl_x, bl_y, bl_w2, (bl_h/2), 'rect-2');
            bl_y += bl_h/2;
            let bl1c = graph.insertVertex(lane2, "bl1c", "999.99M", bl_x, bl_y, bl_w3, (bl_h/2), 'rect-3');

            bl_x = bl_px2;
            bl_y += margin_t1;
            let bl2a = graph.insertVertex(lane2, "bl2a", "Enterprise Conn", bl_x, bl_y, bl2_w1, (bl_h/2), 'rect-1');
            bl_x += bl2_w1;
            let bl2b = graph.insertVertex(lane2, "bl2b", "9999 PROJECT", bl_x, bl_y, bl2_w2, (bl_h/2), 'rect-2');
            bl_x -= bl2_w1;
            bl_y += bl_h/2;
            let bl2c = graph.insertVertex(lane2, "bl2c", "999.99M | 99.9%", bl_x, bl_y, bl2_w3, (bl_h/2), 'rect-3');

            bl_x = bl_px2;
            bl_y += margin_t1;
            let bl3a = graph.insertVertex(lane2, "bl3a", "Satelite", bl_x, bl_y, bl2_w1, (bl_h/2), 'rect-1');
            bl_x += bl2_w1;
            let bl3b = graph.insertVertex(lane2, "bl3b", "9999 PROJECT", bl_x, bl_y, bl2_w2, (bl_h/2), 'rect-2');
            bl_x -= bl2_w1;
            bl_y += bl_h/2;
            let bl3c = graph.insertVertex(lane2, "bl3c", "999.99M | 99.9%", bl_x, bl_y, bl2_w3, (bl_h/2), 'rect-3');
            
            //VERTEX MITRA
            let lane3 = graph.insertVertex(parent, null, 'MITRA', 0, 0, line_width, (y_item*(mt_h*1.5))+100);
            lane3.setConnectable(false);

            let mt_y = margin_t2;
            let mt_x = mt_px;
            let mt1lbl = graph.insertVertex(lane3, "mt1lbl", 'Nilai KL | % Margin Mitra', mt_x, mt_y, mt_width, (mt_h/2), 'rect-lbl');
            mt_y += (mt_h/2);
            let mt1a = graph.insertVertex(lane3, "mt1a", "999.99M", mt_x, mt_y, mt_w1, mt_h, 'rect-3');
            mt_x += mt_w1;
            let mt1b = graph.insertVertex(lane3, "mt1b", "9999 PROJECT", mt_x, mt_y, mt_w2, mt_h, 'rect-2');
            mt_x += mt_w2;
            let mt1c = graph.insertVertex(lane3, "mt1c", "Telkomsat", mt_x, mt_y, mt_w3, mt_h, 'rect-1');
            
            //VERTEX SUB MITRA
            let lane4 = graph.insertVertex(parent, null, 'SUB MITRA', 0, 0, line_width, (y_item*(smt_h*1.5))+100);
            lane4.setConnectable(false);

            let smt_y = margin_t2;
            let smt_x = smt_px;
            let smt1lbl = graph.insertVertex(lane4, "smt1lbl", 'Nilai COGS Own Product atau Nilai beli dari Sub Mitra', smt_x, smt_y, smt_width, (smt_h/2), 'rect-lbl');
            smt_y += (smt_h/2);
            let smt1a = graph.insertVertex(lane4, "smt1a", "999.99M", smt_x, smt_y, smt_w1, smt_h, 'rect-3');
            smt_x += smt_w1;
            let smt1c = graph.insertVertex(lane4, "smt1c", "Own Product", smt_x, smt_y, smt_w2, smt_h, 'rect-1');

            //PARENT EDGE
            graph.insertEdge(parent, 'cust1-bl1', null, cust1c, bl1a,'arrow-1');

            //BUSINESS LINE EDGE
            // graph.insertEdge(lane2, 'bl1-bl2', null, bl1a, bl2c,'arrow-1;entryX=0;entryY=0;exitX=-0;exitY=1');
            graph.insertEdge(lane2, 'bl1c-bl2c', null, bl1c, bl2c,'arrow-2');
            graph.insertEdge(lane2, 'bl1c-bl3c', null, bl1c, bl3c,'arrow-2');

            //MITRA
            graph.insertEdge(parent, 'bl3-mt1', null, bl3b, mt1a,'arrow-3;exitX=0.5;exitY=0.5;entryX=0;entryY=0.5');

            //SUB MITRA
            graph.insertEdge(parent, 'mt1-smt1', null, mt1c, smt1a,'arrow-3;exitX=0.5;exitY=0.5;entryX=0;entryY=0.5');
            
        }
        finally
        {
            // Updates the display
            model.endUpdate();
        }
    }
});
<nav class="toolbox">
     <div class="toolbox-left">
          <div class="toolbox-item toolbox-sort">
               <label>Sort By:</label>

               <div class="select-custom">
                    <select name="orderby" class="form-control">
                         <option value="menu_order" selected="selected">Default sorting</option>
                         <option value="popularity">Sort by popularity</option>
                         <option value="rating">Sort by average rating</option>
                         <option value="date">Sort by newness</option>
                         <option value="price">Sort by price: low to high</option>
                         <option value="price-desc">Sort by price: high to low</option>
                    </select>
               </div><!-- End .select-custom -->

               <a href="#" class="sorter-btn" title="Set Ascending Direction"><span class="sr-only">Set Ascending
                         Direction</span></a>
          </div><!-- End .toolbox-item -->
     </div><!-- End .toolbox-left -->

     <div class="toolbox-item toolbox-show">
          <label>Show:</label>

          <div class="select-custom">
               <select name="count" class="form-control">
                    <option value="9">9 Products</option>
                    <option value="18">18 Products</option>
                    <option value="27">27 Products</option>
               </select>
          </div><!-- End .select-custom -->
     </div><!-- End .toolbox-item -->

     <div class="layout-modes">
          <a href="category.html" class="layout-btn btn-grid active" title="Grid">
               <i class="icon-mode-grid"></i>
          </a>
          <a href="category-list.html" class="layout-btn btn-list" title="List">
               <i class="icon-mode-list"></i>
          </a>
     </div><!-- End .layout-modes -->
</nav>
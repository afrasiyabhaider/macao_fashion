 @php
     $categories = \App\Category::catAndSubCategories(1);
 @endphp
<div class="mobile-menu-overlay"></div><!-- End .mobil-menu-overlay -->

    <div class="mobile-menu-container">
        <div class="mobile-menu-wrapper">
            <span class="mobile-menu-close"><i class="icon-cancel"></i></span>
            <nav class="mobile-nav">
                <ul class="mobile-menu">
                    <li>
                        <a href="{{url('product/list')}}">
                            All Products
                        </a>
                    </li>
                    @foreach ($categories as $parent)
                        @if ($loop->iteration <= 8)
                            <li>
                                    <a href="#" class="sf-with-ul">
                                        {{$parent['name']}}
                                    </a>
                                    
                                    <ul>
                                        <li>
                                            <a href="{{url('products/category/'.encrypt($parent['id']))}}">
                                                {{$parent['name']}}
                                            </a>
                                        </li>
                                        <div class="dropdown-divider"></div>
                                        @if (!empty($parent['sub_categories']))
                                            @foreach ($parent['sub_categories'] as $sub_category)
                                            <li>
                                                        <a href="{{url('products/category/'.encrypt($sub_category['id']))}}">
                                                            {{$sub_category['name']}}
                                                        </a>
                                                </li>
                                            @endforeach
                                        @endif
                                    </ul>
                            </li>
                        @endif
                    @endforeach
                </ul>
            </nav><!-- End .mobile-nav -->

            <div class="social-icons">
                <a href="#" class="social-icon" target="_blank"><i class="icon-facebook"></i></a>
                <a href="#" class="social-icon" target="_blank"><i class="icon-twitter"></i></a>
                <a href="#" class="social-icon" target="_blank"><i class="icon-instagram"></i></a>
            </div><!-- End .social-icons -->
        </div><!-- End .mobile-menu-wrapper -->
    </div><!-- End .mobile-menu-container -->
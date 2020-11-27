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
                    @if ($loop->iteration <= 8 && $parent['name']=="ACCESSOIRE" ) <li>
                        <a href="#" class="sf-with-ul">
                            {{$parent['name']}}
                        </a>
                    
                        <ul>
                            <li>
                                <a href="{{url('products/category/'.encrypt($parent['id']))}}">
                                    {{$parent['name']}}
                                </a>
                            </li>
                            @if (!empty($parent['sub_categories']))
                            @foreach ($parent['sub_categories'] as $sub_category)
                            @php
                            $categories = App\Product::where('sub_category_id',$sub_category['id'])->pluck('refference');
                    
                            $count = App\WebsiteProducts::whereIn('refference',$categories)->count('product_id');
                    
                            @endphp
                            <li>
                                <a href="{{url('products/category/'.encrypt($sub_category['id']))}}">
                                    {{$sub_category['name']}}
                                    [{{$count}}]
                                </a>
                            </li>
                            @endforeach
                            @endif
                        </ul>
                        </li>
                        @endif
                        @endforeach
                        <li>
                            <a href="#" class="sf-with-ul">
                                Women
                            </a>
                            <ul>
                                <li>
                                    <a href="{{url('products/category/'.encrypt(2))}}">
                                        Women
                                    </a>
                                </li>
                                <li>
                                    <a href="{{url('products/category/'.encrypt("top"))}}">
                                        TOP
                                    </a>
                                </li>
                                <li>
                                    <a href="{{url('products/category/'.encrypt("robe"))}}">
                                        ROBE
                                    </a>
                                </li>
                                <li>
                                    <a href="{{url('products/category/'.encrypt("veste"))}}">
                                        VESTE
                                    </a>
                                </li>
                                <li>
                                    <a href="{{url('products/category/'.encrypt("bah"))}}">
                                        BAH
                                    </a>
                                </li>
                                <li>
                                    <a href="{{url('products/category/'.encrypt(48))}}">
                                        CHEMISE
                                    </a>
                                </li>
                                <li>
                                    <a href="{{url('products/category/'.encrypt(35))}}">
                                        ENSAMBLE
                                    </a>
                                </li>
                            </ul>
                        </li>
                </ul>
            </nav><!-- End .mobile-nav -->

            <div class="social-icons">
                <a href="#" class="social-icon" target="_blank"><i class="icon-facebook"></i></a>
                <a href="#" class="social-icon" target="_blank"><i class="icon-twitter"></i></a>
                <a href="#" class="social-icon" target="_blank"><i class="icon-instagram"></i></a>
            </div><!-- End .social-icons -->
        </div><!-- End .mobile-menu-wrapper -->
    </div><!-- End .mobile-menu-container -->
#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
히어로 이미지 적합도 검사
사용:  python3 check-image.py <이미지...>        # 확장 히어로(풀스크린) 기준
       python3 check-image.py --ar 1.0 <이미지>  # 정사각 카드 등 다른 비율

측정 항목과 기준은 아래 SPEC 참조. 감으로 고르지 말고 이걸로 거른 다음 눈으로 본다.
"""
import sys, os, colorsys
import numpy as np
from PIL import Image

TARGET_W = 2560      # 풀스크린 히어로가 대응해야 할 최대 표시 폭 (2x 레티나 1280)
BRAND_HUE = 186.0    # #00ADBD

SPEC = [
 # (키, 라벨, 통과조건 함수, 설명)
 ("upscale","업스케일",       lambda v: v <= 1.05, "크롭 후 가로가 2560 이상이어야 확대 없이 쓴다"),
 ("sharp",  "선명도",         lambda v: v >= 400,  "풀스크린으로 커지면 흐림이 그대로 드러난다"),
 ("contrast","대비",          lambda v: v >= 20,   "낮으면 평평해서 스톡 티가 난다"),
 ("bright", "밝기",           lambda v: 52 <= v <= 78, "너무 어두우면 탁하고, 너무 밝으면 흰 UI가 안 뜬다"),
 ("zoneStd","캡션존 잡음",    lambda v: v <= 12,   "글자 얹을 구석이 조용해야 오버레이를 안 깐다"),
 ("sat",    "채도",           lambda v: v <= 22,   "낮아야 브랜드 시안이 화면에서 유일한 색이 된다"),
]

def crop_ar(im, ar, yfrac=0.5):
    w,h = im.size; th = int(w/ar)
    if th <= h:
        top = int((h-th)*yfrac); return im.crop((0,top,w,top+th))
    tw = int(h*ar); left = int((w-tw)/2); return im.crop((left,0,left+tw,h))

def lap_var(g):
    g = g.astype(float)
    o = (g[:-2,1:-1] + g[1:-1,:-2] - 4*g[1:-1,1:-1] + g[1:-1,2:] + g[2:,1:-1])
    return float(o.var())

def measure(path, ar=1.68, yfrac=0.5):
    im = Image.open(path).convert("RGB"); ow, oh = im.size
    c = crop_ar(im, ar, yfrac); cw, _ = c.size
    small = c.resize((640, max(1,int(640/ar))))
    a = np.asarray(small, float); g = a.mean(axis=2)
    H, W = g.shape
    zone = g[int(H*0.70):, :int(W*0.45)]          # 캡션이 앉는 좌하단
    mr, mg, mb = (a.reshape(-1,3).mean(axis=0)/255)
    hh, ss, vv = colorsys.rgb_to_hsv(mr, mg, mb)
    hue = hh*360
    return dict(file=os.path.basename(path), orig="%dx%d"%(ow,oh), cropW=cw,
        upscale=TARGET_W/cw, sharp=lap_var(g), contrast=g.std()/255*100,
        bright=g.mean()/255*100, zoneStd=zone.std()/255*100, sat=ss*100,
        hue=hue, dHue=min(abs(hue-BRAND_HUE), 360-abs(hue-BRAND_HUE)))

def main(argv):
    ar, yf, files = 1.68, 0.5, []
    i = 0
    while i < len(argv):
        if argv[i] == "--ar": ar = float(argv[i+1]); i += 2
        elif argv[i] == "--y": yf = float(argv[i+1]); i += 2
        else: files.append(argv[i]); i += 1
    if not files:
        print(__doc__); return 1
    rows = [measure(f, ar, yf) for f in files]
    w = max(len(r["file"]) for r in rows)
    head = "%-*s %-11s %8s %8s %7s %7s %9s %7s %6s" % (w,"파일","원본","업스케일","선명도","대비","밝기","캡션존","채도","ΔHue")
    print(head); print("-"*len(head))
    for r in sorted(rows, key=lambda x: -x["sharp"]):
        print("%-*s %-11s %7.2fx %8.0f %7.1f %7.1f %9.1f %7.1f %6.0f" % (
            w, r["file"], r["orig"], r["upscale"], r["sharp"], r["contrast"],
            r["bright"], r["zoneStd"], r["sat"], r["dHue"]))
    print()
    for r in sorted(rows, key=lambda x: -x["sharp"]):
        bad = [(lab, r[k], why) for k, lab, ok, why in SPEC if not ok(r[k])]
        mark = "통과" if not bad else "%d개 미달" % len(bad)
        print("· %s — %s" % (r["file"], mark))
        for lab, v, why in bad:
            print("    %-10s %7.1f   %s" % (lab, v, why))
    return 0

if __name__ == "__main__":
    sys.exit(main(sys.argv[1:]))
